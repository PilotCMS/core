<?php

namespace Pilot\Core\Console\Commands;

use Illuminate\Console\Command;
use Pilot\Core\Models\Asset;
use Pilot\Core\Support\Cms\AssetThumbnailer;

class GenerateAssetThumbnails extends Command
{
    protected $signature = 'pilot:generate-asset-thumbnails {--force : Regenerate existing thumbnails}';

    protected $description = 'Generate optimized WebP previews for image assets';

    public function handle(AssetThumbnailer $thumbnailer): int
    {
        $query = Asset::query()
            ->where('mime', 'like', 'image/%')
            ->where('mime', '!=', 'image/svg+xml')
            ->whereIn('disk', array_keys(config('filesystems.disks', [])));

        if (! $this->option('force')) {
            $query->whereNull('thumbnail_path');
        }

        $total = (clone $query)->count();
        $generated = 0;
        $failed = 0;
        $bar = $this->output->createProgressBar($total);

        $query->chunkById(100, function ($assets) use ($thumbnailer, &$generated, &$failed, $bar): void {
            foreach ($assets as $asset) {
                $thumbnailer->generate($asset, (bool) $this->option('force')) === null
                    ? $failed++
                    : $generated++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Generated {$generated} thumbnail(s); {$failed} skipped or failed.");

        return self::SUCCESS;
    }
}
