<?php

namespace Pilot\Core\Support\Cms;

readonly class AssetImageVariant
{
    public function __construct(
        public string $path,
        public string $mime,
        public string $etag,
    ) {}
}
