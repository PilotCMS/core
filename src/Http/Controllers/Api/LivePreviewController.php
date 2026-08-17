<?php

namespace Pilot\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Pilot\Core\Http\Controllers\Controller;
use Pilot\Core\Http\Requests\Api\LivePreviewRenderRequest;
use Pilot\Core\Models\CmsSetting;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\Space;
use Pilot\Laravel\Support\ContentRenderer;

class LivePreviewController extends Controller
{
    public function __invoke(LivePreviewRenderRequest $request, ContentRenderer $renderer): JsonResponse
    {
        $locale = $request->string('locale', CmsSetting::get('default_locale', app()->getLocale()))->toString();

        $contentModel = $request->source() === 'headless' ? null : $this->resolveContent($request);

        $content = $contentModel
            ? $renderer->fromModel($contentModel, $locale)
            : $renderer->fromHeadless($this->headlessPayload($request->validated()), $locale);

        $contentPayload = $content->toArray();

        if ($contentModel) {
            $contentPayload['categories'] = $this->taxonomyValues($contentModel, 'categories');
            $contentPayload['tags'] = $this->taxonomyValues($contentModel, 'tags');
        }

        return response()->json([
            'html' => $renderer->renderBlocks($content)->toHtml(),
            'content' => $contentPayload,
            'source' => $content->source,
        ]);
    }

    protected function resolveContent(LivePreviewRenderRequest $request): Content
    {
        if ($request->filled('content_id')) {
            return $this->authorizePreviewContent($request, Content::query()->findOrFail($request->integer('content_id')));
        }

        $spaceSlug = $request->string('space', CmsSetting::get('default_space', config('pilot.default_space')))->toString();
        $slug = $request->string('slug', CmsSetting::get('home_slug', config('pilot.home_slug', 'home')))->toString();

        $space = $spaceSlug
            ? Space::query()->where('slug', $spaceSlug)->firstOrFail()
            : Space::query()->orderBy('id')->firstOrFail();

        return $this->authorizePreviewContent($request, Content::query()
            ->where('space_id', $space->id)
            ->where('type', 'page')
            ->where('slug', $slug)
            ->firstOrFail());
    }

    protected function authorizePreviewContent(LivePreviewRenderRequest $request, Content $content): Content
    {
        if ($content->isPublished() || $request->hasValidSignature() || $request->user()) {
            return $content;
        }

        abort(403, 'Draft preview content requires an authenticated or signed request.');
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

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function headlessPayload(array $payload): array
    {
        if (isset($payload['content']) || isset($payload['story'])) {
            return $payload;
        }

        return [
            'content' => [
                'slug' => $payload['slug'] ?? 'preview',
                'name' => $payload['name'] ?? 'Preview',
                'body' => $payload['body'] ?? $payload['blocks'] ?? [],
            ],
        ];
    }
}
