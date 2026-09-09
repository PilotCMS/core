<?php

namespace Pilot\Core\Support\Cms;

use Pilot\Core\Contracts\AssetImageUrlGenerator;
use Pilot\Core\Models\Asset;

class LocalAssetImageUrlGenerator implements AssetImageUrlGenerator
{
    public function url(Asset $asset, array $options = []): string
    {
        $width = (int) ($options['width'] ?? 0);
        $height = (int) ($options['height'] ?? 0);

        if (($width > 0) !== ($height > 0)) {
            throw new \InvalidArgumentException('Image width and height must be positive integers.');
        }

        $query = [];

        if ($width > 0 && $height > 0) {
            $query = [
                'size' => "{$width}x{$height}",
                'v' => $asset->imageCacheVersion(),
            ];
        }

        foreach (['fit', 'format', 'quality'] as $option) {
            if (isset($options[$option])) {
                $query[$option] = $options[$option];
            }
        }

        return route('pilot.assets.image', [
            'asset' => $asset,
            'filename' => $asset->filename,
            ...$query,
        ], absolute: false);
    }
}
