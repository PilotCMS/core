<?php

namespace Pilot\Core\Console\Commands;

use Illuminate\Console\Command;
use Pilot\Core\Support\Updates\PilotUpdateChecker;
use Pilot\Core\Support\Updates\PilotUpdateManager;
use Pilot\Core\Support\Updates\PilotUpdateSafety;
use Symfony\Component\Process\Process;
use Throwable;

class RunPilotUpdateInBackground extends Command
{
    protected $signature = 'pilot:update-background {--target=} {--initiated-by=}';

    protected $description = 'Run a Pilot update initiated from the admin interface';

    public function handle(PilotUpdateManager $manager, PilotUpdateChecker $checker, PilotUpdateSafety $safety): int
    {
        $target = (string) $this->option('target');
        $state = $manager->running($target, $this->option('initiated-by'));
        $backup = null;
        $manageMaintenanceMode = (bool) config('cms.updates.maintenance_mode', true)
            && ! app()->isDownForMaintenance();

        try {
            $manager->assertComposerFilesUnchanged();
            $manager->progress('Running pre-update safety checks…');
            $checks = $safety->preflight();
            $manager->appendLog("Preflight checks passed:\n- ".implode("\n- ", $checks)."\n");

            if ($manageMaintenanceMode) {
                $this->runArtisan(['down', '--retry=60'], $manager, 'Pilot could not enter maintenance mode.');
            }

            $manager->progress('Creating a rollback backup…', ['checks' => $checks]);
            $backup = $safety->createBackup((string) ($state['update_id'] ?? now()->format('YmdHis')));
            $manager->appendLog("Rollback backup created at {$backup['path']}\n");
            $manager->progress('Updating dependencies, migrating, and rebuilding assets…', ['backup' => $backup]);

            $process = new Process([
                PHP_BINARY,
                'artisan',
                'pilot:update',
                '--target='.$target,
                '--force',
            ], base_path(), timeout: null);
            $exitCode = $process->run(function (string $type, string $output) use ($manager): void {
                $manager->appendLog($output);
            });

            if ($exitCode !== self::SUCCESS) {
                throw new \RuntimeException('The Pilot update command failed.');
            }

            $manager->progress('Verifying the updated application…');
            $checks = [...$checks, ...$safety->postflight($target, fn (string $output) => $manager->appendLog($output))];

            if ($manageMaintenanceMode) {
                $this->runArtisan(['up'], $manager, 'Pilot could not leave maintenance mode.');
            }

            $urlCheck = $safety->checkHealthUrl();

            if ($urlCheck !== null) {
                $checks[] = $urlCheck;
            }

            $manager->markComposerFilesAsUpdaterOwned();
            $checker->forget();
            $manager->progress('Pilot was updated successfully.', ['checks' => $checks]);
            $manager->finish(true, 'Pilot was updated successfully. Backup and health checks completed.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $manager->appendLog("Update failed: {$exception->getMessage()}\n");
            $rollbackSucceeded = false;
            $rollbackMessage = 'No rollback was needed because the backup had not completed.';

            if (is_array($backup)) {
                try {
                    $manager->progress('The update failed. Restoring the previous version…');

                    if ($manageMaintenanceMode && ! app()->isDownForMaintenance()) {
                        $this->runArtisan(['down', '--retry=60'], $manager, 'Pilot could not enter maintenance mode for rollback.');
                    }

                    $safety->rollback($backup, fn (string $output) => $manager->appendLog($output));
                    $rollbackSucceeded = true;
                    $rollbackMessage = 'Automatic rollback completed successfully.';
                } catch (Throwable $rollbackException) {
                    $rollbackMessage = 'Automatic rollback failed: '.$rollbackException->getMessage();
                    $manager->appendLog($rollbackMessage."\n");
                }
            }

            if ($manageMaintenanceMode) {
                try {
                    $this->runArtisan(['up'], $manager, 'Pilot could not leave maintenance mode after rollback.');
                } catch (Throwable $upException) {
                    $rollbackSucceeded = false;
                    $rollbackMessage .= ' The application may still be in maintenance mode: '.$upException->getMessage();
                    $manager->appendLog($rollbackMessage."\n");
                }
            }

            $checker->forget();
            $manager->progress('The update failed.', [
                'rollback' => [
                    'succeeded' => $rollbackSucceeded,
                    'message' => $rollbackMessage,
                    'finished_at' => now()->toIso8601String(),
                ],
            ]);
            $manager->finish(false, 'The update failed. '.$rollbackMessage.' Review the log for details.');

            return self::FAILURE;
        }
    }

    /** @param list<string> $arguments */
    private function runArtisan(array $arguments, PilotUpdateManager $manager, string $failure): void
    {
        $process = new Process([PHP_BINARY, 'artisan', ...$arguments], base_path(), timeout: null);
        $exitCode = $process->run(fn (string $type, string $output) => $manager->appendLog($output));

        if ($exitCode !== self::SUCCESS) {
            throw new \RuntimeException($failure);
        }
    }
}
