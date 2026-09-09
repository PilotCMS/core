<?php

namespace Pilot\Core\Support\Cms;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Pilot\Core\Models\Asset;
use RuntimeException;

class AssetImageTransformer
{
    public function transform(Asset $asset, AssetImageTransformation $transformation): AssetImageVariant
    {
        $disk = Storage::disk($asset->disk);
        $key = hash('sha256', $asset->imageCacheVersion().'|'.$transformation->cacheKey());
        $path = "assets/transforms/{$asset->id}/{$key}.{$transformation->extension()}";

        if (! $disk->exists($path)) {
            $this->generate($disk, $asset, $transformation, $path);
        }

        return new AssetImageVariant($path, $transformation->mime(), '"'.$key.'"');
    }

    protected function generate(
        FilesystemAdapter $disk,
        Asset $asset,
        AssetImageTransformation $transformation,
        string $path,
    ): void {
        $source = $disk->readStream($asset->path);

        if ($source === false) {
            throw new RuntimeException('Unable to read the source image.');
        }

        try {
            $contents = class_exists(\Imagick::class)
                ? $this->withImagick($source, $asset, $transformation)
                : $this->withGd($source, $asset, $transformation);
        } finally {
            fclose($source);
        }

        if (! $disk->put($path, $contents)) {
            throw new RuntimeException('Unable to cache the transformed image.');
        }
    }

    /** @param resource $source */
    protected function withImagick($source, Asset $asset, AssetImageTransformation $transformation): string
    {
        if (! AssetImageTransformation::supports($transformation->format)) {
            throw new RuntimeException("The {$transformation->format} image format is not supported.");
        }

        $image = new \Imagick;
        $image->readImageFile($source);
        $image->setIteratorIndex(0);
        $image->autoOrient();
        $width = $image->getImageWidth();
        $height = $image->getImageHeight();
        [$cropX, $cropY, $cropWidth, $cropHeight, $outputWidth, $outputHeight] = $this->geometry(
            $width,
            $height,
            $asset,
            $transformation,
        );

        if ($transformation->fit === 'cover') {
            $image->cropImage($cropWidth, $cropHeight, $cropX, $cropY);
            $image->setImagePage(0, 0, 0, 0);
        }

        $image->resizeImage($outputWidth, $outputHeight, \Imagick::FILTER_LANCZOS, 1);
        $image->setImageFormat($transformation->format);
        $image->setImageCompressionQuality($transformation->quality);
        $image->stripImage();
        $contents = $image->getImageBlob();
        $image->clear();
        $image->destroy();

        return $contents;
    }

    /** @param resource $source */
    protected function withGd($source, Asset $asset, AssetImageTransformation $transformation): string
    {
        if (! AssetImageTransformation::supports($transformation->format)) {
            throw new RuntimeException("The {$transformation->format} image format is not supported.");
        }

        $original = imagecreatefromstring(stream_get_contents($source));

        if ($original === false) {
            throw new RuntimeException('Unable to decode the source image.');
        }

        [$cropX, $cropY, $cropWidth, $cropHeight, $outputWidth, $outputHeight] = $this->geometry(
            imagesx($original),
            imagesy($original),
            $asset,
            $transformation,
        );
        $target = imagecreatetruecolor($outputWidth, $outputHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);

        imagecopyresampled(
            $target,
            $original,
            0,
            0,
            $transformation->fit === 'cover' ? $cropX : 0,
            $transformation->fit === 'cover' ? $cropY : 0,
            $outputWidth,
            $outputHeight,
            $transformation->fit === 'cover' ? $cropWidth : imagesx($original),
            $transformation->fit === 'cover' ? $cropHeight : imagesy($original),
        );

        ob_start();
        match ($transformation->format) {
            'avif' => imageavif($target, null, $transformation->quality),
            'webp' => imagewebp($target, null, $transformation->quality),
            'png' => imagepng($target, null, (int) round((100 - $transformation->quality) * 9 / 100)),
            default => imagejpeg($target, null, $transformation->quality),
        };
        $contents = ob_get_clean();
        imagedestroy($target);
        imagedestroy($original);

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to encode the transformed image.');
        }

        return $contents;
    }

    /** @return array{int, int, int, int, int, int} */
    protected function geometry(
        int $sourceWidth,
        int $sourceHeight,
        Asset $asset,
        AssetImageTransformation $transformation,
    ): array {
        if ($transformation->fit === 'contain') {
            $scale = min($transformation->width / $sourceWidth, $transformation->height / $sourceHeight);

            if (! config('cms.images.allow_upscale', false)) {
                $scale = min($scale, 1);
            }

            return [0, 0, $sourceWidth, $sourceHeight, max(1, (int) round($sourceWidth * $scale)), max(1, (int) round($sourceHeight * $scale))];
        }

        $targetRatio = $transformation->width / $transformation->height;
        $sourceRatio = $sourceWidth / $sourceHeight;

        if ($sourceRatio > $targetRatio) {
            $cropHeight = $sourceHeight;
            $cropWidth = max(1, (int) round($sourceHeight * $targetRatio));
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = max(1, (int) round($sourceWidth / $targetRatio));
        }

        $focalX = $sourceWidth * ($asset->focalX() / 100);
        $focalY = $sourceHeight * ($asset->focalY() / 100);
        $cropX = (int) round(max(0, min($sourceWidth - $cropWidth, $focalX - ($cropWidth / 2))));
        $cropY = (int) round(max(0, min($sourceHeight - $cropHeight, $focalY - ($cropHeight / 2))));
        $scale = $transformation->width / $cropWidth;

        if (! config('cms.images.allow_upscale', false)) {
            $scale = min($scale, 1);
        }

        return [
            $cropX,
            $cropY,
            $cropWidth,
            $cropHeight,
            max(1, (int) round($cropWidth * $scale)),
            max(1, (int) round($cropHeight * $scale)),
        ];
    }
}
