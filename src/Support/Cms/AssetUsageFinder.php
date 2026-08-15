<?php

namespace Pilot\Core\Support\Cms;

use Pilot\Core\Models\Asset;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\Content;
use Illuminate\Support\Collection;

class AssetUsageFinder
{
    /**
     * @return Collection<int, array{
     *     content: Content,
     *     block: Block|null,
     *     location: string,
     *     value: string
     * }>
     */
    public function forAsset(Asset $asset): Collection
    {
        $needles = $this->assetNeedles($asset);

        if ($needles === []) {
            return collect();
        }

        $references = collect();

        Content::query()
            ->where('space_id', $asset->space_id)
            ->with('allBlocks')
            ->orderBy('name')
            ->get()
            ->each(function (Content $content) use ($needles, $references): void {
                foreach ($this->matchingValues($content->meta ?? [], $needles, 'meta') as $match) {
                    $references->push([
                        'content' => $content,
                        'block' => null,
                        'location' => $match['path'],
                        'value' => $match['value'],
                    ]);
                }

                $content->allBlocks->each(function (Block $block) use ($content, $needles, $references): void {
                    foreach ($this->matchingValues($block->data ?? [], $needles, 'blocks.'.$block->id.'.data') as $match) {
                        $references->push([
                            'content' => $content,
                            'block' => $block,
                            'location' => $match['path'],
                            'value' => $match['value'],
                        ]);
                    }
                });
            });

        return $references->unique(fn (array $reference): string => implode('|', [
            $reference['content']->id,
            $reference['block']?->id ?? 'meta',
            $reference['location'],
            $reference['value'],
        ]))->values();
    }

    public function countForAsset(Asset $asset): int
    {
        return $this->forAsset($asset)->count();
    }

    /**
     * @return array<int, string>
     */
    protected function assetNeedles(Asset $asset): array
    {
        return collect([
            $asset->path,
            $asset->url(),
            $asset->relativeUrl(),
            $asset->fullUrl(),
        ])
            ->filter(fn (?string $value): bool => filled($value))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @param  array<int, string>  $needles
     * @return array<int, array{path: string, value: string}>
     */
    protected function matchingValues(array $data, array $needles, string $path): array
    {
        $matches = [];

        foreach ($data as $key => $value) {
            $childPath = $path.'.'.$key;

            if (is_array($value)) {
                array_push($matches, ...$this->matchingValues($value, $needles, $childPath));

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            foreach ($needles as $needle) {
                if ($value === $needle) {
                    $matches[] = [
                        'path' => $childPath,
                        'value' => $value,
                    ];

                    break;
                }
            }
        }

        return $matches;
    }
}
