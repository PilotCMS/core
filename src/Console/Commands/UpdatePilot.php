<?php

namespace Pilot\Core\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class UpdatePilot extends Command
{
    protected $signature = 'pilot:update
        {--dry-run : Show whether an update is available without changing files}
        {--no-build : Skip the frontend production build}
        {--force : Continue when composer.json or composer.lock has uncommitted changes}';

    protected $description = 'Update Pilot Core and run its post-update steps';

    public function handle(): int
    {
        if (! $this->option('force') && $this->composerFilesAreDirty()) {
            $this->error('composer.json or composer.lock has uncommitted changes. Commit them or use --force.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            return $this->runStep(['composer', 'outdated', 'pilotcms/core', '--direct'], 'Checking Pilot Core');
        }

        if ($this->runStep([
            'composer', 'update', 'pilotcms/core', '--with-all-dependencies', '--no-interaction',
        ], 'Updating Pilot Core') !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($this->runStep([PHP_BINARY, 'artisan', 'migrate', '--force'], 'Running database migrations') !== self::SUCCESS) {
            return self::FAILURE;
        }

        if (! $this->option('no-build') && file_exists(base_path('package.json'))) {
            if ($this->runStep(['npm', 'run', 'build'], 'Building frontend assets') !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('Pilot is up to date.');

        return self::SUCCESS;
    }

    private function composerFilesAreDirty(): bool
    {
        if (! is_dir(base_path('.git'))) {
            return false;
        }

        $process = new Process(['git', 'status', '--porcelain', '--', 'composer.json', 'composer.lock'], base_path());
        $process->run();

        return $process->isSuccessful() && trim($process->getOutput()) !== '';
    }

    /** @param list<string> $command */
    private function runStep(array $command, string $label): int
    {
        $this->components->task($label, function () use ($command, &$successful): void {
            $process = new Process($command, base_path(), timeout: null);
            $process->setTty(Process::isTtySupported());
            $successful = $process->run(function (string $type, string $output): void {
                $this->output->write($output);
            }) === 0;
        });

        return ($successful ?? false) ? self::SUCCESS : self::FAILURE;
    }
}
