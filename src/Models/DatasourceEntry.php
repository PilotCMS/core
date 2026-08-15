<?php

namespace Pilot\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatasourceEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'datasource_id',
        'key',
        'value',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'order' => 'integer',
        ];
    }

    public function datasource(): BelongsTo
    {
        return $this->belongsTo(Datasource::class);
    }
}
