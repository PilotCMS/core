<?php

namespace Pilot\Core\GraphQL\Queries;

use Pilot\Core\Models\Content;
use Pilot\Core\Models\Space;

class ContentQuery
{
    public function __invoke($root, array $args)
    {
        $space = Space::where('slug', $args['space'])->firstOrFail();
        $locale = $args['locale'] ?? 'en';
        $version = $args['version'] ?? 'published';

        $query = Content::where('space_id', $space->id)
            ->where('slug', $args['slug'])
            ->where('type', 'page');

        if ($version === 'published') {
            $query->where('status', 'published')
                ->whereNotNull('published_at');
        }

        $content = $query->with(['blocks' => function ($q) {
            $q->whereNull('parent_block_id')->orderBy('position');
        }, 'blocks.children'])->firstOrFail();

        return $content;
    }
}
