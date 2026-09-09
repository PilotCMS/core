<?php

namespace Pilot\Core\Support\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;

class ContentCollectionResolver
{
    /**
     * @var Collection<string, BlockType>|null
     */
    protected ?Collection $blockTypes = null;

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array<string, mixed>>
     */
    public function resolveBlocks(array $blocks, Model $owner, string $locale): array
    {
        return collect($blocks)->map(function (array $block) use ($owner, $locale): array {
            $blockType = $this->blockType((string) ($block['component'] ?? $block['type'] ?? ''));
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            foreach ($blockType?->schema['fields'] ?? [] as $field) {
                if (($field['type'] ?? null) !== 'content_collection') {
                    continue;
                }

                $key = (string) ($field['key'] ?? '');
                $configuration = is_array($data[$key] ?? null) ? $data[$key] : [];
                $data['_content_collections'][$key] = $configuration;
                $data[$key] = $this->resolve($owner, $field, $configuration, $locale);
            }

            $block['data'] = $data;
            $block['children'] = $this->resolveBlocks($block['children'] ?? [], $owner, $locale);

            return $block;
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<string, mixed>  $configuration
     * @return array<int, array<string, mixed>>
     */
    public function resolve(Model $owner, array $field, array $configuration, string $locale): array
    {
        $contentType = trim((string) ($field['source_content_type'] ?? ''));
        $mappings = collect($field['mappings'] ?? [])->filter(
            fn (mixed $mapping): bool => is_array($mapping)
                && filled($mapping['target'] ?? null)
                && filled($mapping['source'] ?? null)
        );

        if ($contentType === '' || $mappings->isEmpty()) {
            return [];
        }

        $query = Content::query()
            ->where('space_id', $owner->getAttribute('space_id'))
            ->where('type', 'page')
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->whereHas('contentType', fn (Builder $query): Builder => $query->where('key', $contentType));

        if ($owner->getKey()) {
            $query->whereKeyNot($owner->getKey());
        }

        $this->applyTaxonomyFilter($query, 'categories', $configuration['categories'] ?? []);
        $this->applyTaxonomyFilter($query, 'tags', $configuration['tags'] ?? []);

        $orderBy = in_array($configuration['order_by'] ?? null, $this->orderableFields(), true)
            ? $configuration['order_by']
            : 'published_at';
        $direction = ($configuration['order_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $limit = max(1, min(50, (int) ($configuration['limit'] ?? 6)));

        return $query
            ->orderBy($orderBy, $direction)
            ->limit($limit)
            ->get()
            ->map(function (Content $content) use ($mappings, $locale): array {
                $item = [
                    '_content_id' => $content->id,
                    '_content_type' => $content->contentType?->key,
                    '_slug' => $content->slug,
                ];

                foreach ($mappings as $mapping) {
                    Arr::set(
                        $item,
                        (string) $mapping['target'],
                        $this->sourceValue($content, (string) $mapping['source'], $locale),
                    );
                }

                return $item;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>|string  $values
     */
    protected function applyTaxonomyFilter(Builder $query, string $column, array|string $values): void
    {
        $values = is_string($values) ? explode(',', $values) : $values;
        $values = collect($values)
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        if ($values->isEmpty()) {
            return;
        }

        $query->where(function (Builder $query) use ($column, $values): void {
            foreach ($values as $value) {
                $query->orWhereJsonContains($column, $value);
            }
        });
    }

    protected function sourceValue(Content $content, string $source, string $locale): mixed
    {
        $value = match ($source) {
            '$id' => $content->id,
            '$url' => '/'.ltrim($content->slug, '/'),
            default => data_get([
                'name' => $content->name,
                'slug' => $content->slug,
                'status' => $content->status,
                'published_at' => $content->published_at?->toIso8601String(),
                'created_at' => $content->created_at?->toIso8601String(),
                'updated_at' => $content->updated_at?->toIso8601String(),
                'categories' => $content->categories ?? [],
                'tags' => $content->tags ?? [],
                'meta' => $content->meta ?? [],
            ], $source),
        };

        if (is_array($value) && array_key_exists($locale, $value)) {
            return $value[$locale];
        }

        return $value;
    }

    /**
     * @return array<int, string>
     */
    public function orderableFields(): array
    {
        return ['name', 'slug', 'published_at', 'created_at', 'updated_at'];
    }

    protected function blockType(string $key): ?BlockType
    {
        $this->blockTypes ??= BlockType::query()->get()->keyBy('key');

        return $this->blockTypes->get($key);
    }
}
