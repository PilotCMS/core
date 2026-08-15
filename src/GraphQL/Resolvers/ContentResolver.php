<?php

namespace Pilot\Core\GraphQL\Resolvers;

use Pilot\Core\Models\Content;

class ContentResolver
{
    public function publishedAt(Content $content): ?string
    {
        return $content->published_at?->toIso8601String();
    }

    public function body(Content $content): array
    {
        $blocks = $content->blocks()->with('children')->orderBy('position')->get();

        return $blocks->map(function ($block) {
            return [
                '_uid' => $block->id,
                'component' => $block->type,
                'data' => $block->data,
                'children' => $block->children->map(function ($child) {
                    return [
                        '_uid' => $child->id,
                        'component' => $child->type,
                        'data' => $child->data,
                        'children' => [],
                    ];
                })->toArray(),
            ];
        })->toArray();
    }
}
