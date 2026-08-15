<?php

namespace Pilot\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pilot\Laravel\Support\PreviewUrl;

class SpacePreviewTarget extends Model
{
    /** @use HasFactory<\Database\Factories\SpacePreviewTargetFactory> */
    use HasFactory;

    protected $fillable = [
        'space_id',
        'name',
        'url',
        'sort_order',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function previewUrlFor(Content $content, ?int $expiresMinutes = null): string
    {
        return app(PreviewUrl::class)->forContent($content, $this->url, $expiresMinutes);
    }
}
