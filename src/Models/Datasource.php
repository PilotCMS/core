<?php

namespace Pilot\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Datasource extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'name',
        'slug',
    ];

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(DatasourceEntry::class)->orderBy('order');
    }
}
