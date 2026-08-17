<?php

namespace Pilot\Core\Console\Commands;

use Illuminate\Console\Command;
use Pilot\Core\Support\Updates\PilotUpdateChecker;
use Pilot\Core\Support\Updates\PilotUpdateManager;
use Symfony\Component\Process\Process;
use Throwable;

class RunPilotUpdateInBackground extends Command
{
    protected $signature = 'pilot:update-background {--target=} {--initiated-by=}';

    protected $description = 'Run a Pilot update initiated from the admin interface';

    public function handle(PilotUpdateManager $manager, PilotUpdateChecker $checker): int
    {
        $target = (string) $this->option('target');
        $manager->running($target, $this->option('initiated-by'));

        try {
            $process = new Process([
                PHP_BINARY,
                'artisan',
                'pilot:update',
                '--target='.$target,
            ], base_path(), timeout: null);
            $exitCode = $process->run(function (string $type, string $output) use ($manager): void {
                $manager->appendLog($output);
            });

            $checker->forget();

            if ($exitCode !== self::SUCCESS) {
                $manager->finish(false, 'The update failed. Review the log for details.');

                return self::FAILURE;
            }

            $manager->finish(true, 'Pilot was updated successfully.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $manager->appendLog($exception->getMessage().PHP_EOL);
            $manager->finish(false, 'The update failed. Review the log for details.');

            return self::FAILURE;
        }
    }
}
