<?php

namespace Pilot\Core\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class FinalizePilotUpdate extends Command
{
    protected $signature = 'pilot:finalize-update {--no-build : Skip the frontend production build}';

    protected $description = 'Apply post-Composer steps for a Pilot update';

    public function handle(): int
    {
        if ($this->call('pilot:sync-host') !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if (! $this->option('no-build') && file_exists(base_path('package.json'))) {
            $install = new Process(['npm', 'ci', '--no-audit', '--no-fund'], base_path(), timeout: null);
            $install->run(fn (string $type, string $output) => $this->output->write($output));

            if (! $install->isSuccessful()) {
                return self::FAILURE;
            }

            $process = new Process(['npm', 'run', 'build'], base_path(), timeout: null);
            $process->run(fn (string $type, string $output) => $this->output->write($output));

            if (! $process->isSuccessful()) {
                return self::FAILURE;
            }
        }

        $this->call('optimize:clear');
        $this->newLine();
        $this->info('Pilot is up to date.');

        return self::SUCCESS;
    }
}
