<?php

namespace Pilot\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'space_id',
        'parent_id',
        'content_type_id',
        'type',
        'slug',
        'name',
        'status',
        'workflow_status',
        'published_at',
        'scheduled_for',
        'review_requested_at',
        'review_requested_by',
        'reviewer_id',
        'review_due_at',
        'review_note',
        'published_revision_id',
        'meta',
        'categories',
        'tags',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'review_requested_at' => 'datetime',
            'review_due_at' => 'datetime',
            'meta' => 'array',
            'categories' => 'array',
            'tags' => 'array',
        ];
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ContentRevision::class)->orderByDesc('created_at');
    }

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }

    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(ContentRevision::class, 'published_revision_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo((string) config('auth.providers.users.model', 'App\Models\User'), 'reviewer_id');
    }

    public function reviewRequester(): BelongsTo
    {
        return $this->belongsTo((string) config('auth.providers.users.model', 'App\Models\User'), 'review_requested_by');
    }

    public function references(): HasMany
    {
        return $this->hasMany(ContentReference::class);
    }

    public function incomingReferences(): HasMany
    {
        return $this->hasMany(ContentReference::class, 'target_content_id');
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Content::class, 'parent_id')->orderBy('name');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class)->whereNull('parent_block_id')->orderBy('position');
    }

    public function allBlocks(): HasMany
    {
        return $this->hasMany(Block::class)->orderBy('position');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo((string) config('auth.providers.users.model', 'App\Models\User'), 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo((string) config('auth.providers.users.model', 'App\Models\User'), 'updated_by');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    public function isFolder(): bool
    {
        return $this->type === 'folder';
    }

    public function isPage(): bool
    {
        return $this->type === 'page';
    }

    public function isScheduled(): bool
    {
        return $this->workflow_status === 'scheduled' && $this->scheduled_for !== null;
    }
}
