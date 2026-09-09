<?php

namespace Pilot\Core\Support\Cms;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

readonly class AssetImageTransformation
{
    public function __construct(
        public int $width,
        public int $height,
        public string $fit,
        public string $format,
        public int $quality,
    ) {}

    public static function fromRequest(Request $request, string $sourceMime): self
    {
        $input = Validator::make($request->query(), [
            'size' => ['required', 'string', 'regex:/^[1-9][0-9]{0,4}x[1-9][0-9]{0,4}$/'],
            'fit' => ['sometimes', Rule::in(['cover', 'contain'])],
            'format' => ['sometimes', Rule::in(['auto', 'avif', 'webp', 'jpeg', 'jpg', 'png'])],
            'quality' => ['sometimes', 'integer', 'between:40,95'],
            'v' => ['sometimes', 'string', 'max:64'],
        ])->validate();

        [$width, $height] = array_map('intval', explode('x', $input['size']));
        $maxWidth = max(1, (int) config('cms.images.max_width', 4096));
        $maxHeight = max(1, (int) config('cms.images.max_height', 4096));
        $maxPixels = max(1, (int) config('cms.images.max_pixels', 16777216));

        Validator::make(compact('width', 'height'), [
            'width' => ['integer', "max:{$maxWidth}"],
            'height' => ['integer', "max:{$maxHeight}"],
        ])->after(function ($validator) use ($width, $height, $maxPixels): void {
            if ($width * $height > $maxPixels) {
                $validator->errors()->add('size', 'The requested image contains too many pixels.');
            }
        })->validate();

        $format = self::resolveFormat(
            $input['format'] ?? 'auto',
            (string) $request->header('Accept'),
            $sourceMime,
        );

        return new self(
            $width,
            $height,
            $input['fit'] ?? 'cover',
            $format,
            (int) ($input['quality'] ?? config('cms.images.default_quality', 80)),
        );
    }

    public function extension(): string
    {
        return $this->format === 'jpeg' ? 'jpg' : $this->format;
    }

    public function mime(): string
    {
        return 'image/'.$this->format;
    }

    public function cacheKey(): string
    {
        return implode('|', [$this->width, $this->height, $this->fit, $this->format, $this->quality]);
    }

    protected static function resolveFormat(string $requested, string $accept, string $sourceMime): string
    {
        $requested = $requested === 'jpg' ? 'jpeg' : $requested;

        if ($requested !== 'auto') {
            return $requested;
        }

        if (str_contains($accept, 'image/avif') && self::supports('avif')) {
            return 'avif';
        }

        if ((str_contains($accept, 'image/webp') || str_contains($accept, '*/*')) && self::supports('webp')) {
            return 'webp';
        }

        return match ($sourceMime) {
            'image/png' => 'png',
            default => 'jpeg',
        };
    }

    public static function supports(string $format): bool
    {
        if (class_exists(\Imagick::class) && \Imagick::queryFormats(strtoupper($format)) !== []) {
            return true;
        }

        return match ($format) {
            'avif' => function_exists('imageavif'),
            'webp' => function_exists('imagewebp'),
            'jpeg' => function_exists('imagejpeg'),
            'png' => function_exists('imagepng'),
            default => false,
        };
    }
}
