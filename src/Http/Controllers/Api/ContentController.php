<?php

namespace Pilot\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pilot\Core\Http\Controllers\Controller;
use Pilot\Core\Http\Resources\Cms\ContentResource;
use Pilot\Core\Models\CmsSetting;
use Pilot\Laravel\Models\Content;
use Pilot\Laravel\Models\Space;
use Pilot\Laravel\Support\ContentRenderer;

class ContentController extends Controller
{
    public function index(Request $request, ContentRenderer $renderer, $spaceSlug): JsonResponse
    {
        $space = Space::where('slug', $spaceSlug)->firstOrFail();
        $locale = $request->get('locale', CmsSetting::get('default_locale', 'en'));
        $version = $request->get('version', 'published');

        $query = Content::where('space_id', $space->id)
            ->where('type', 'page');

        if ($version === 'published') {
            $query->where('status', 'published')
                ->whereNotNull('published_at');
        } elseif ($version === 'draft') {
            if (! CmsSetting::get('draft_api_enabled', true)) {
                return response()->json(['error' => 'Draft API access is disabled'], 403);
            }

            // Require Sanctum token for draft access
            if (! $request->user()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $contents = $query->with(['blocks' => function ($q) {
            $q->whereNull('parent_block_id')->orderBy('position');
        }, 'blocks.children'])->get();

        return response()->json([
            'contents' => ContentResource::collection(
                $contents->map(fn (Content $content): array => $this->renderedContent($content, $renderer, $locale))
            ),
        ]);
    }

    public function show(Request $request, ContentRenderer $renderer, $spaceSlug, $slug): JsonResponse
    {
        $space = Space::where('slug', $spaceSlug)->firstOrFail();
        $locale = $request->get('locale', CmsSetting::get('default_locale', 'en'));
        $version = $request->get('version', 'published');

        $query = Content::where('space_id', $space->id)
            ->where('slug', $slug)
            ->where('type', 'page');

        if ($version === 'published') {
            $query->where('status', 'published')
                ->whereNotNull('published_at');
        } elseif ($version === 'draft') {
            if (! CmsSetting::get('draft_api_enabled', true)) {
                return response()->json(['error' => 'Draft API access is disabled'], 403);
            }

            if (! $request->user()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $content = $query->with(['blocks' => function ($q) {
            $q->whereNull('parent_block_id')->orderBy('position');
        }, 'blocks.children'])->firstOrFail();

        $renderedContent = $this->renderedContent($content, $renderer, $locale);

        return response()->json([
            'story' => new ContentResource($renderedContent),
            'content' => new ContentResource($renderedContent),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function renderedContent(Content $content, ContentRenderer $renderer, string $locale): array
    {
        return array_merge($renderer->fromModel($content, $locale)->toArray(), [
            'categories' => $this->taxonomyValues($content, 'categories'),
            'tags' => $this->taxonomyValues($content, 'tags'),
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function taxonomyValues(Content $content, string $field): array
    {
        $value = $content->getAttribute($field);

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? array_values($value) : [];
    }
}
