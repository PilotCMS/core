<?php

namespace Pilot\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Pilot\Core\Models\Asset;
use Pilot\Core\Support\Cms\AssetImageTransformation;
use Pilot\Core\Support\Cms\AssetImageTransformer;
use Symfony\Component\HttpFoundation\Response;

class AssetImageController
{
    public function __invoke(
        Request $request,
        Asset $asset,
        string $filename,
        AssetImageTransformer $transformer,
    ): Response {
        abort_unless($asset->isImage() && $asset->mime !== 'image/svg+xml' && $asset->hasConfiguredDisk(), 404);
        abort_unless(hash_equals($asset->filename, $filename), 404);

        $disk = Storage::disk($asset->disk);
        abort_unless($disk->exists($asset->path), 404);

        $version = $asset->imageCacheVersion();

        if ($request->query('v') !== $version) {
            return redirect()->to($request->fullUrlWithQuery(['v' => $version]));
        }

        if (! $request->has('size')) {
            $etag = '"original-'.$version.'"';

            if ($request->header('If-None-Match') === $etag) {
                return response('', 304, [
                    'Cache-Control' => config('cms.images.cache_control'),
                    'ETag' => $etag,
                ]);
            }

            return $disk->response($asset->path, $asset->filename, [
                'Cache-Control' => config('cms.images.cache_control'),
                'Content-Type' => $asset->mime,
                'ETag' => $etag,
            ]);
        }

        $transformation = AssetImageTransformation::fromRequest($request, (string) $asset->mime);
        abort_unless(AssetImageTransformation::supports($transformation->format), 422);
        $variant = $transformer->transform($asset, $transformation);

        if ($request->header('If-None-Match') === $variant->etag) {
            return response('', 304, [
                'Cache-Control' => config('cms.images.cache_control'),
                'ETag' => $variant->etag,
                'Vary' => 'Accept',
            ]);
        }

        return $disk->response($variant->path, pathinfo($asset->filename, PATHINFO_FILENAME).'.'.pathinfo($variant->path, PATHINFO_EXTENSION), [
            'Cache-Control' => config('cms.images.cache_control'),
            'Content-Type' => $variant->mime,
            'ETag' => $variant->etag,
            'Vary' => 'Accept',
        ]);
    }
}
