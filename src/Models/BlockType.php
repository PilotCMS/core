<?php

namespace Pilot\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockType extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'icon',
        'schema',
        'is_global',
        'folder_id',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'is_global' => 'boolean',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(BlockTypeFolder::class, 'folder_id');
    }
}
