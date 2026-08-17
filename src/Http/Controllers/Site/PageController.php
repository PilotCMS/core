<?php

namespace Pilot\Core\Http\Controllers\Site;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Pilot\Core\Http\Controllers\Controller;
use Pilot\Core\Models\CmsSetting;
use Pilot\Laravel\Facades\Pilot;
use Pilot\Laravel\Models\Content;
use Pilot\Laravel\Models\Redirect;
use Pilot\Laravel\Models\Space;
use Pilot\Laravel\Support\ContentRenderer;

class PageController extends Controller
{
    public function __construct(
        protected ContentRenderer $renderer,
    ) {}

    public function home(): View|RedirectResponse
    {
        return $this->renderPage(CmsSetting::get('home_slug', config('pilot.home_slug', 'home')));
    }

    public function show(string $slug): View|RedirectResponse
    {
        return $this->renderPage($slug);
    }

    protected function renderPage(string $slug): View|RedirectResponse
    {
        $space = $this->resolveSpace();

        if (! $space) {
            throw new ModelNotFoundException('No space configured for public site rendering.');
        }

        $content = Pilot::content()
            ->space($space)
            ->slug($slug)
            ->published()
            ->withBlocks()
            ->first();

        if (! $content) {
            $redirect = $this->resolveRedirect($space, $slug);

            if ($redirect) {
                $redirect->increment('hit_count');
                $redirect->update(['last_hit_at' => now()]);

                return redirect($redirect->destination, $redirect->status_code);
            }

            throw (new ModelNotFoundException)->setModel(Content::class);
        }

        $payload = $this->renderer->fromModel($content, CmsSetting::get('default_locale', app()->getLocale()));

        return $this->renderer->pageView($payload, space: $space);
    }

    protected function resolveSpace(): ?Space
    {
        $spaceSlug = CmsSetting::get('default_space', config('pilot.default_space'));

        if ($spaceSlug) {
            $space = Space::query()->where('slug', $spaceSlug)->first();

            if ($space) {
                return $space;
            }
        }

        return Space::query()->orderBy('id')->first();
    }

    protected function resolveRedirect(Space $space, string $slug): ?Redirect
    {
        return Redirect::query()
            ->where('space_id', $space->id)
            ->where('source', '/'.trim($slug, '/'))
            ->where('is_active', true)
            ->first();
    }
}
