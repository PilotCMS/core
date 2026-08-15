<?php

namespace Pilot\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class AssetTag extends Model
{
    use HasFactory;

    protected $fillable = ['space_id', 'name', 'slug'];

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'asset_asset_tag');
    }

    public static function findOrCreateFromNames(Space $space, array $names): array
    {
        $tags = [];
        foreach ($names as $name) {
            $name = trim($name);
            if (empty($name)) {
                continue;
            }

            $slug = Str::slug($name);
            $tag = $space->assetTags()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );
            $tags[] = $tag;
        }

        return $tags;
    }
}
