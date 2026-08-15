<?php

namespace Pilot\Core\GraphQL\Resolvers;

class BlockResolver
{
    public function uid($block): string
    {
        return (string) ($block['_uid'] ?? $block['id'] ?? '');
    }

    public function component($block): string
    {
        return $block['component'] ?? '';
    }

    public function data($block): array
    {
        return $block['data'] ?? [];
    }

    public function children($block): array
    {
        return $block['children'] ?? [];
    }
}
