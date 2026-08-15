<?php

namespace Pilot\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditorPreference extends Model
{
    protected $fillable = [
        'user_id',
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo((string) config('auth.providers.users.model', 'App\Models\User'));
    }

    public static function get(int $userId, string $key, mixed $default = null): mixed
    {
        $pref = static::where('user_id', $userId)->where('key', $key)->first();

        return $pref ? $pref->value : $default;
    }

    public static function set(int $userId, string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );
    }
}
