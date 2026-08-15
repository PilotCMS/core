<?php

namespace Pilot\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentType extends Model
{
    /** @use HasFactory<\Database\Factories\ContentTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'description',
        'schema',
        'allowed_blocks',
        'settings',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'allowed_blocks' => 'array',
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }
}
