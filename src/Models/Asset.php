<?php

namespace Pilot\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'folder_id',
        'disk',
        'path',
        'thumbnail_path',
        'filename',
        'display_name',
        'description',
        'mime',
        'size',
        'width',
        'height',
        'focal_x',
        'focal_y',
        'alt',
        'title',
        'credit',
        'copyright',
        'license',
        'source_url',
        'checksum',
        'expires_at',
        'metadata',
    ];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(AssetTag::class, 'asset_asset_tag');
    }

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'focal_x' => 'float',
            'focal_y' => 'float',
            'alt' => 'array',
            'title' => 'array',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(AssetFolder::class, 'folder_id');
    }

    public function url(): string
    {
        if (! $this->hasConfiguredDisk()) {
            return $this->path;
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function relativeUrl(): string
    {
        if (! $this->hasConfiguredDisk()) {
            return $this->url();
        }

        return static::toRelativeUrl($this->url());
    }

    public function thumbnailUrl(): string
    {
        if (! $this->thumbnail_path || ! $this->hasConfiguredDisk()) {
            return $this->optimizedExternalThumbnailUrl($this->url());
        }

        return Storage::disk($this->disk)->url($this->thumbnail_path);
    }

    public function thumbnailRelativeUrl(): string
    {
        return static::toRelativeUrl($this->thumbnailUrl());
    }

    protected function optimizedExternalThumbnailUrl(string $url): string
    {
        $parts = parse_url($url);

        if (($parts['host'] ?? '') !== 'images.unsplash.com') {
            return $url;
        }

        parse_str($parts['query'] ?? '', $query);
        $query['fit'] = 'crop';
        $query['w'] = 640;
        $query['h'] = 480;
        $query['q'] = 78;

        return ($parts['scheme'] ?? 'https').'://'.$parts['host'].($parts['path'] ?? '').'?'.http_build_query($query);
    }

    public function hasConfiguredDisk(): bool
    {
        if (! app()->bound('config')) {
            return false;
        }

        return array_key_exists($this->disk, config('filesystems.disks', []));
    }

    public function focalX(): float
    {
        return $this->focal_x ?? 50.0;
    }

    public function focalY(): float
    {
        return $this->focal_y ?? 50.0;
    }

    public static function toRelativeUrl(string $url): string
    {
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return $url;
        }

        $parts = parse_url($url);
        $urlHost = strtolower($parts['host'] ?? '');
        $localHosts = ['localhost', '127.0.0.1', '::1'];

        if (app()->bound('config')) {
            $localHosts[] = parse_url((string) config('app.url'), PHP_URL_HOST);
        }

        if (app()->bound('request')) {
            $localHosts[] = request()->getHost();
        }

        $localHosts = array_filter(array_map(
            fn ($host) => strtolower((string) $host),
            $localHosts,
        ));

        if ($urlHost === '' || ! in_array($urlHost, $localHosts, true)) {
            return $url;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $path.$query.$fragment;
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime ?? '', 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime ?? '', 'video/');
    }

    public function isDocument(): bool
    {
        $docMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
        ];

        return in_array($this->mime ?? '', $docMimes) || str_starts_with($this->mime ?? '', 'application/');
    }

    public function displayName(): string
    {
        return $this->display_name ?? $this->filename;
    }

    public function dimensions(): ?string
    {
        if (! $this->width || ! $this->height) {
            return null;
        }

        return $this->width.' x '.$this->height;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function fullUrl(): string
    {
        $url = $this->url();

        return str_starts_with($url, 'http') ? $url : url($url);
    }

    public function getAltAttribute($value): ?string
    {
        if (is_string($value) && str_starts_with($value, '{')) {
            $value = json_decode($value, true) ?: $value;
        }

        if (is_array($value)) {
            return $value['en'] ?? array_values($value)[0] ?? null;
        }

        return $value;
    }

    public function getTitleAttribute($value): ?string
    {
        if (is_string($value) && str_starts_with($value, '{')) {
            $value = json_decode($value, true) ?: $value;
        }

        if (is_array($value)) {
            return $value['en'] ?? array_values($value)[0] ?? null;
        }

        return $value;
    }
}
