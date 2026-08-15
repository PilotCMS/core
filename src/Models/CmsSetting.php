<?php

namespace Pilot\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CmsSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        return $setting ? data_get($setting->value, 'value', $default) : $default;
    }

    public static function set(string $key, mixed $value): static
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => ['value' => $value]],
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public static function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            static::set($key, $value);
        }
    }

    public static function previewSecret(): string
    {
        $secret = config('pilot.preview.secret');

        if (is_string($secret) && $secret !== '') {
            return $secret;
        }

        $secret = static::get('preview_secret');

        if (! is_string($secret) || $secret === '') {
            $secret = 'pilot_'.Str::random(64);
            static::set('preview_secret', $secret);
        }

        config(['pilot.preview.secret' => $secret]);

        return $secret;
    }
}
