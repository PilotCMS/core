<?php

namespace Pilot\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlockTypeFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'parent_id',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(BlockTypeFolder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(BlockTypeFolder::class, 'parent_id')->orderBy('name');
    }

    public function blockTypes(): HasMany
    {
        return $this->hasMany(BlockType::class, 'folder_id')->orderBy('name');
    }
}
