<?php

namespace Pilot\Core\Support\Cms;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Pilot\Core\Models\Asset;
use Throwable;

class AssetThumbnailer
{
    public const WIDTH = 640;

    public const HEIGHT = 480;

    public function generate(Asset $asset, bool $force = false): ?string
    {
        if (! $asset->isImage() || ! $asset->hasConfiguredDisk() || $asset->mime === 'image/svg+xml') {
            return null;
        }

        if ($asset->thumbnail_path && ! $force) {
            return $asset->thumbnail_path;
        }

        try {
            $disk = Storage::disk($asset->disk);
            $source = $disk->readStream($asset->path);

            if ($source === false) {
                return null;
            }

            try {
                $contents = class_exists(\Imagick::class)
                    ? $this->withImagick($source)
                    : $this->withGd($source);
            } finally {
                fclose($source);
            }

            if ($contents === null) {
                return null;
            }

            $path = 'assets/thumbnails/'.$asset->id.'.webp';
            $disk->put($path, $contents);
            $asset->forceFill(['thumbnail_path' => $path])->saveQuietly();

            return $path;
        } catch (Throwable $exception) {
            Log::warning('Unable to generate asset thumbnail.', [
                'asset_id' => $asset->id,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /** @param resource $source */
    protected function withImagick($source): string
    {
        $image = new \Imagick;
        $image->readImageFile($source);
        $image->setIteratorIndex(0);
        $image->autoOrient();
        $image->thumbnailImage(self::WIDTH, self::HEIGHT, true);
        $image->setImageFormat('webp');
        $image->setImageCompressionQuality(78);
        $image->stripImage();
        $contents = $image->getImageBlob();
        $image->clear();
        $image->destroy();

        return $contents;
    }

    /** @param resource $source */
    protected function withGd($source): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return null;
        }

        $original = imagecreatefromstring(stream_get_contents($source));

        if ($original === false) {
            return null;
        }

        $width = imagesx($original);
        $height = imagesy($original);
        $scale = min(self::WIDTH / $width, self::HEIGHT / $height, 1);
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        imagecopyresampled($thumbnail, $original, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagewebp($thumbnail, null, 78);
        $contents = ob_get_clean();
        imagedestroy($thumbnail);
        imagedestroy($original);

        return is_string($contents) ? $contents : null;
    }
}
