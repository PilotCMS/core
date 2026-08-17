<?php

namespace Pilot\Core\Console\Commands;

use Illuminate\Console\Command;
use Pilot\Core\Support\Installation\HostSynchronizer;
use Symfony\Component\Process\Process;

class SyncPilotHost extends Command
{
    protected $signature = 'pilot:sync-host';

    protected $description = 'Connect the Laravel host to the versioned Pilot Core application';

    public function handle(HostSynchronizer $synchronizer): int
    {
        $changes = $synchronizer->sync(base_path());

        if ($changes === []) {
            $this->components->info('Pilot host integration is up to date.');

            return self::SUCCESS;
        }

        foreach ($changes as $path) {
            $this->components->twoColumnDetail($path, '<fg=green>UPDATED</>');
        }

        if (in_array('composer.json', $changes, true)) {
            $process = new Process(['composer', 'dump-autoload', '--no-interaction'], base_path(), timeout: null);
            $process->run(fn (string $type, string $output) => $this->output->write($output));

            if (! $process->isSuccessful()) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
