<?php

namespace Pilot\Core\Contracts;

use Pilot\Core\Models\Asset;

interface AssetImageUrlGenerator
{
    public function url(Asset $asset, array $options = []): string;
}
