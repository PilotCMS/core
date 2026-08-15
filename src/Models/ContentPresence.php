<?php

namespace Pilot\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPresence extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id',
        'user_id',
        'selected_block_id',
        'status',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function selectedBlock(): BelongsTo
    {
        return $this->belongsTo(Block::class, 'selected_block_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo((string) config('auth.providers.users.model', 'App\Models\User'));
    }
}
