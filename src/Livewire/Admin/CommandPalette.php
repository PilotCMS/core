<?php

namespace Pilot\Core\Livewire\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Pilot\Core\Extensions\ExtensionRegistry;
use Pilot\Core\Models\Asset;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\Datasource;
use Pilot\Core\Models\Space;

class CommandPalette extends Component
{
    public string $search = '';

    public function render(): View
    {
        return view('livewire.admin.command-palette', [
            'groups' => $this->groups,
        ]);
    }

    /**
     * @return array<int, array{label: string, results: array<int, array{title: string, description: string, url: string, icon: string}>}>
     */
    public function getGroupsProperty(): array
    {
        $term = trim($this->search);

        if ($term === '') {
            return $this->quickLinkGroups();
        }

        return collect([
            $this->contentResults($term),
            $this->assetResults($term),
            $this->blockTypeResults($term),
            $this->datasourceResults($term),
            $this->spaceResults($term),
            $this->userResults($term),
            ...$this->extensionCommandGroups($term),
        ])
            ->filter(fn (array $group): bool => $group['results'] !== [])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, results: array<int, array{title: string, description: string, url: string, icon: string}>}>
     */
    protected function quickLinkGroups(): array
    {
        $workspace = [
            $this->result('Dashboard', 'Workspace overview and recent activity', route('admin.dashboard'), 'layout-dashboard'),
            $this->result('Content', 'Pages, folders, and global content', route('admin.content.index'), 'files'),
            $this->result('Assets', 'Images, documents, and media library', route('admin.assets.index'), 'image'),
            $this->result('Datasources', 'Reusable structured data entries', route('admin.datasources.index'), 'database'),
        ];

        $admin = [];

        if ($this->isAdmin()) {
            $admin[] = $this->result('Blocks', 'Reusable CMS block types', route('admin.blocks.index'), 'boxes');
            $admin[] = $this->result('Types', 'Content type definitions', route('admin.content-types.index'), 'panels-top-left');
            $admin[] = $this->result('Spaces', 'Workspace and locale settings', route('admin.spaces.index'), 'layers-3');
            $admin[] = $this->result('Settings', 'CMS settings and configuration', route('admin.settings.index'), 'settings');
        }

        if (auth()->user()?->can('manage users')) {
            $admin[] = $this->result('Users', 'Manage accounts, roles, and permissions', route('admin.users.index'), 'users');
        }

        $groups = collect([
            ['label' => 'Go to', 'results' => $workspace],
            ['label' => 'Admin', 'results' => $admin],
        ])->keyBy('label');

        foreach ($this->extensionCommandGroups() as $extensionGroup) {
            $existing = $groups->get($extensionGroup['label'], [
                'label' => $extensionGroup['label'],
                'results' => [],
            ]);
            $existing['results'] = array_merge($existing['results'], $extensionGroup['results']);
            $groups->put($extensionGroup['label'], $existing);
        }

        return $groups
            ->filter(fn (array $group): bool => $group['results'] !== [])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, results: array<int, array{title: string, description: string, url: string, icon: string}>}>
     */
    protected function extensionCommandGroups(?string $term = null): array
    {
        return collect(app(ExtensionRegistry::class)->commandPaletteItems())
            ->filter(fn (array $item): bool => ! isset($item['can']) || (auth()->user()?->can($item['can']) ?? false))
            ->filter(function (array $item) use ($term): bool {
                if ($term === null || $term === '') {
                    return true;
                }

                return Str::contains(
                    Str::lower($item['title'].' '.$item['description']),
                    Str::lower($term),
                );
            })
            ->groupBy('group')
            ->map(fn (Collection $items, string $group): array => [
                'label' => $group,
                'results' => $items
                    ->map(fn (array $item): array => $this->result(
                        $item['title'],
                        $item['description'],
                        route($item['route']),
                        $item['icon'],
                    ))
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{label: string, results: array<int, array{title: string, description: string, url: string, icon: string}>}
     */
    protected function contentResults(string $term): array
    {
        $results = Content::query()
            ->where(function ($query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            })
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (Content $content): array => $this->result(
                $content->name,
                ucfirst($content->type).' / '.$content->slug,
                route('admin.content.edit', $content),
                $content->isFolder() ? 'folder' : 'file-text'
            ));

        return $this->group('Content', $results);
    }

    /**
     * @return array{label: string, results: array<int, array{title: string, description: string, url: string, icon: string}>}
     */
    protected function assetResults(string $term): array
    {
        $results = Asset::query()
            ->where(function ($query) use ($term): void {
                $query->where('filename', 'like', "%{$term}%")
                    ->orWhere('display_name', 'like', "%{$term}%")
                    ->orWhere('mime', 'like', "%{$term}%");
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Asset $asset): array => $this->result(
                $asset->displayName(),
                $asset->mime ?: 'Asset',
                route('admin.assets.index'),
                $asset->isImage() ? 'image' : 'file'
            ));

        return $this->group('Assets', $results);
    }

    /**
     * @return array{label: string, results: array<int, array{title: string, description: string, url: string, icon: string}>}
     */
    protected function blockTypeResults(string $term): array
    {
        if (! $this->isAdmin()) {
            return $this->group('Blocks', collect());
        }

        $results = BlockType::query()
            ->where(function ($query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('key', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (BlockType $blockType): array => $this->result(
                $blockType->name,
                'Block type / '.$blockType->key,
                route('admin.blocks.edit', $blockType),
                'boxes'
            ));

        return $this->group('Blocks', $results);
    }

    /**
     * @return array{label: string, results: array<int, array{title: string, description: string, url: string, icon: string}>}
     */
    protected function datasourceResults(string $term): array
    {
        $results = Datasource::query()
            ->where(function ($query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (Datasource $datasource): array => $this->result(
                $datasource->name,
                'Datasource / '.$datasource->slug,
                route('admin.datasources.index', ['search' => $datasource->slug]),
                'database'
            ));

        return $this->group('Datasources', $results);
    }

    /**
     * @return array{label: string, results: array<int, array{title: string, description: string, url: string, icon: string}>}
     */
    protected function spaceResults(string $term): array
    {
        if (! $this->isAdmin()) {
            return $this->group('Spaces', collect());
        }

        $results = Space::query()
            ->where(function ($query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (Space $space): array => $this->result(
                $space->name,
                'Space / '.$space->slug,
                route('admin.spaces.edit', $space),
                'layers-3'
            ));

        return $this->group('Spaces', $results);
    }

    /**
     * @return array{label: string, results: array<int, array{title: string, description: string, url: string, icon: string}>}
     */
    protected function userResults(string $term): array
    {
        if (! auth()->user()?->can('manage users')) {
            return $this->group('Users', collect());
        }

        $results = User::query()
            ->where(function ($query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (User $user): array => $this->result(
                $user->name,
                $user->email,
                route('admin.users.index', ['search' => $user->email]),
                'user'
            ));

        return $this->group('Users', $results);
    }

    protected function isAdmin(): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }

    /**
     * @param  Collection<int, array{title: string, description: string, url: string, icon: string}>  $results
     * @return array{label: string, results: array<int, array{title: string, description: string, url: string, icon: string}>}
     */
    protected function group(string $label, Collection $results): array
    {
        return [
            'label' => $label,
            'results' => $results->values()->all(),
        ];
    }

    /**
     * @return array{title: string, description: string, url: string, icon: string}
     */
    protected function result(string $title, string $description, string $url, string $icon): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'icon' => $icon,
        ];
    }
}
