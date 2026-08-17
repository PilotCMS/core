<?php

namespace Pilot\Core\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Pilot\Core\Http\Controllers\Controller;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\ContentRevision;
use Pilot\Laravel\Support\ContentRenderer;

class ContentPreviewController extends Controller
{
    public function __invoke(Content $content, ContentRenderer $renderer, Request $request): View
    {
        if ($request->filled('revision')) {
            $revision = ContentRevision::query()
                ->where('content_id', $content->id)
                ->findOrFail($request->integer('revision'));

            $payload = $renderer->fromHeadless($this->payloadFromRevision($content, $revision));

            return $renderer->pageView($payload, space: $content->space);
        }

        $payload = $renderer->fromModel($content);

        return $renderer->pageView($payload, space: $content->space);
    }

    /**
     * @return array<string, mixed>
     */
    protected function payloadFromRevision(Content $content, ContentRevision $revision): array
    {
        $snapshot = $revision->snapshot;
        $contentSnapshot = $snapshot['content'] ?? [];

        return [
            'id' => $content->id,
            'type' => $content->type,
            'slug' => $contentSnapshot['slug'] ?? $content->slug,
            'name' => $contentSnapshot['name'] ?? $content->name,
            'content_type' => $content->contentType?->key,
            'status' => $contentSnapshot['status'] ?? 'draft',
            'published_at' => null,
            'meta' => $contentSnapshot['meta'] ?? [],
            'blocks' => collect($snapshot['blocks'] ?? [])
                ->map(fn (array $block): array => $this->payloadBlockFromSnapshot($block))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>
     */
    protected function payloadBlockFromSnapshot(array $block): array
    {
        return [
            'id' => $block['id'] ?? 'revision-'.md5(json_encode($block) ?: ''),
            'type' => $block['type'] ?? 'unknown',
            'data' => $block['data'] ?? [],
            'children' => collect($block['children'] ?? [])
                ->map(fn (array $child): array => $this->payloadBlockFromSnapshot($child))
                ->values()
                ->all(),
        ];
    }
}
