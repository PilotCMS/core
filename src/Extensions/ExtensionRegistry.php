<?php

namespace Pilot\Core\Extensions;

use Illuminate\Http\Request;
use InvalidArgumentException;

class ExtensionRegistry
{
    /**
     * @var array<string, array<string, array{route: string, active: string, icon: string, label: string, can?: string, order: int}>>
     */
    protected array $navigationItems = [
        'workspace' => [],
        'admin' => [],
    ];

    /**
     * @var array<string, array{group: string, route: string, title: string, description: string, icon: string, can?: string, order: int}>
     */
    protected array $commandPaletteItems = [];

    /** @var array<string, string> */
    protected array $pageTitles = [];

    public function navigationItem(
        string $section,
        string $route,
        string $label,
        string $icon,
        ?string $active = null,
        ?string $permission = null,
        int $order = 100,
    ): static {
        if (! array_key_exists($section, $this->navigationItems)) {
            throw new InvalidArgumentException("Unknown Pilot navigation section [{$section}].");
        }

        $item = [
            'route' => $route,
            'active' => $active ?? $route,
            'icon' => $icon,
            'label' => $label,
            'order' => $order,
        ];

        if ($permission !== null) {
            $item['can'] = $permission;
        }

        $this->navigationItems[$section][$route] = $item;

        return $this;
    }

    /**
     * @return array<int, array{route: string, active: string, icon: string, label: string, can?: string, order: int}>
     */
    public function navigationItems(string $section): array
    {
        if (! array_key_exists($section, $this->navigationItems)) {
            throw new InvalidArgumentException("Unknown Pilot navigation section [{$section}].");
        }

        $items = array_values($this->navigationItems[$section]);

        usort($items, fn (array $left, array $right): int => [$left['order'], $left['label']] <=> [$right['order'], $right['label']]);

        return $items;
    }

    public function commandPaletteItem(
        string $group,
        string $route,
        string $title,
        string $description,
        string $icon,
        ?string $permission = null,
        int $order = 100,
    ): static {
        $item = [
            'group' => $group,
            'route' => $route,
            'title' => $title,
            'description' => $description,
            'icon' => $icon,
            'order' => $order,
        ];

        if ($permission !== null) {
            $item['can'] = $permission;
        }

        $this->commandPaletteItems[$group.'|'.$route] = $item;

        return $this;
    }

    /**
     * @return array<int, array{group: string, route: string, title: string, description: string, icon: string, can?: string, order: int}>
     */
    public function commandPaletteItems(): array
    {
        $items = array_values($this->commandPaletteItems);

        usort($items, fn (array $left, array $right): int => [$left['order'], $left['title']] <=> [$right['order'], $right['title']]);

        return $items;
    }

    public function pageTitle(string $routePattern, string $title): static
    {
        $this->pageTitles[$routePattern] = $title;

        return $this;
    }

    public function pageTitleFor(Request $request): ?string
    {
        foreach ($this->pageTitles as $routePattern => $title) {
            if ($request->routeIs($routePattern)) {
                return $title;
            }
        }

        return null;
    }
}
