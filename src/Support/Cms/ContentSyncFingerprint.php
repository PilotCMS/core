<?php

namespace Pilot\Core\Support\Cms;

use Pilot\Core\Models\Block;
use Pilot\Core\Models\Content;

class ContentSyncFingerprint
{
    public static function make(Content $content): string
    {
        $blocks = $content->allBlocks()
            ->get([
                'id',
                'parent_block_id',
                'type',
                'position',
                'data',
                'reusable_source_block_id',
                'reusable_key',
                'reusable_name',
            ])
            ->map(fn (Block $block): array => self::normalizeBlock($block->toArray()))
            ->values()
            ->all();

        return self::hash($content, $blocks);
    }

    public static function makeFromBlocks(Content $content, array $blocks): string
    {
        return self::hash($content, self::flattenBlocks($blocks));
    }

    protected static function hash(Content $content, array $blocks): string
    {
        return hash('sha256', json_encode([
            'content' => [
                'id' => $content->id,
                'name' => $content->name,
                'slug' => $content->slug,
                'status' => $content->status,
                'workflow_status' => $content->workflow_status,
                'meta' => $content->meta ?? [],
                'categories' => $content->categories ?? [],
                'tags' => $content->tags ?? [],
                'updated_at' => $content->updated_at?->toJSON(),
            ],
            'blocks' => $blocks,
        ]) ?: '');
    }

    protected static function flattenBlocks(array $blocks): array
    {
        $flattened = [];

        foreach ($blocks as $block) {
            $flattened[] = self::normalizeBlock($block);
            $flattened = array_merge($flattened, self::flattenBlocks($block['children'] ?? []));
        }

        return $flattened;
    }

    protected static function normalizeBlock(array $block): array
    {
        return [
            'id' => $block['id'],
            'parent_block_id' => $block['parent_block_id'] ?? null,
            'type' => $block['type'],
            'position' => $block['position'],
            'data' => $block['data'] ?? [],
            'reusable_source_block_id' => $block['reusable_source_block_id'] ?? null,
            'reusable_key' => $block['reusable_key'] ?? null,
            'reusable_name' => $block['reusable_name'] ?? null,
        ];
    }
}
