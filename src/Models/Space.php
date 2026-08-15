<?php

namespace Pilot\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Space extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function locales(): HasMany
    {
        return $this->hasMany(Locale::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function previewTargets(): HasMany
    {
        return $this->hasMany(SpacePreviewTarget::class)->orderBy('sort_order')->orderBy('name');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function assetFolders(): HasMany
    {
        return $this->hasMany(AssetFolder::class);
    }

    public function assetTags(): HasMany
    {
        return $this->hasMany(AssetTag::class);
    }

    public function datasources(): HasMany
    {
        return $this->hasMany(Datasource::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function defaultLocale(): ?Locale
    {
        return $this->locales()->where('is_default', true)->first();
    }
}
