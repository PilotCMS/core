<?php

namespace Pilot\Core\Console\Commands;

use Illuminate\Console\Command;
use Pilot\Core\Support\Installation\HostSynchronizer;

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

        return self::SUCCESS;
    }
}
