<?php

namespace TornadoEngine\Nav;

use Closure;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;

/**
 * The site's navigation menu. Modules add their items in their service provider's boot():
 *
 *     $nav->add('Notes', 'notes.index', active: 'notes.*', can: 'notes.view');
 *     $nav->group('tools', 'Tools', order: 40);
 *     $nav->add('Inventory', 'inventory.index', group: 'tools');
 *     $nav->user('Administration', 'administration', can: 'administration');
 *     $nav->tool('Notes', 'notes.index', 'Write and share notes.', section: 'Classroom', icon: Nav::ICONS['document'], can: 'notes.view');
 *
 * An item with "can" only shows to users with that permission. A dropdown only shows if
 * the user can see at least one item in it.
 */
class Nav
{
    /** @var array<string, NavItem> */
    private array $items = [];

    /** @var array<string, NavItem> */
    private array $userItems = [];

    /** @var array<string, array{label: string, order: int}> */
    private array $groups = [];

    /** @var array<string, NavTool> */
    private array $tools = [];

    /** Colours a dashboard card can have. */
    public const COLORS = ['indigo', 'blue', 'green', 'teal', 'amber', 'orange', 'red', 'purple', 'gray'];

    /** A few Heroicons (outline, 24×24) for dashboard cards. Modules can pass their own path. */
    public const ICONS = [
        'cog' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
        'puzzle' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>',
        'terminal' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        'book' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
        'document' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'chat' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>',
        'lock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>',
        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    ];

    /**
     * Adds an item to the top bar, or to a dropdown in it. Replaces the item for the same route.
     *
     * @param  string|null  $active  Route name pattern for the current page; defaults to the route itself.
     * @param  int  $order  Lower numbers come first.
     * @param  string|null  $can  Permission needed to see the item.
     * @param  string|null  $group  Key of a dropdown made with group().
     * @param  Closure(?Authenticatable): bool|null  $visible
     */
    public function add(string $label, string $route, ?string $active = null, int $order = 100, ?string $can = null, ?string $group = null, ?Closure $visible = null): static
    {
        $this->items[$route] = new NavItem($label, $route, $active ?? $route, $order, $can, $group, $visible);

        return $this;
    }

    /**
     * Adds a dropdown to the top bar. Put items in it with add(..., group: $key).
     */
    public function group(string $key, string $label, int $order = 100): static
    {
        $this->groups[$key] = ['label' => $label, 'order' => $order];

        return $this;
    }

    /**
     * Adds an item to the menu under the user's name, above Log Out.
     *
     * @param  Closure(?Authenticatable): bool|null  $visible
     */
    public function user(string $label, string $route, ?string $active = null, int $order = 100, ?string $can = null, ?Closure $visible = null): static
    {
        $this->userItems[$route] = new NavItem($label, $route, $active ?? $route, $order, $can, null, $visible);

        return $this;
    }

    /**
     * Adds a card to the dashboard, in a section (for example "Google" or "Administration").
     * It only shows to people who can open it.
     *
     * @param  string  $icon  A key of ICONS, or the inside of a 24×24 outline SVG.
     * @param  Closure(?Authenticatable): bool|null  $visible
     */
    public function tool(string $label, string $route, string $description, string $section = 'Tools', string $icon = 'puzzle', string $color = 'indigo', ?string $can = null, ?Closure $visible = null): static
    {
        $this->tools[$route] = new NavTool($label, $route, $description, $section, self::ICONS[$icon] ?? $icon,
            in_array($color, self::COLORS, true) ? $color : 'indigo', $can, $visible);

        return $this;
    }

    /**
     * This user's dashboard: [section => cards], sections and cards in name order.
     *
     * @return array<string, list<NavTool>>
     */
    public function toolsFor(?Authenticatable $user): array
    {
        $sections = [];
        foreach ($this->visible($this->tools, $user) as $tool) {
            $sections[$tool->section][] = $tool;
        }

        uksort($sections, 'strnatcasecmp');
        foreach ($sections as &$tools) {
            usort($tools, fn (NavTool $a, NavTool $b) => strnatcasecmp($a->label, $b->label));
        }

        return $sections;
    }

    public function remove(string $route): static
    {
        unset($this->items[$route], $this->userItems[$route], $this->tools[$route]);

        return $this;
    }

    /**
     * The top bar for this user, in order: items and dropdowns.
     *
     * @return list<NavItem|NavGroup>
     */
    public function for(?Authenticatable $user): array
    {
        $visible = $this->visible($this->items, $user);
        $entries = array_values(array_filter($visible, fn (NavItem $item) => $item->group === null || ! isset($this->groups[$item->group])));

        foreach ($this->groups as $key => $group) {
            $items = array_values(array_filter($visible, fn (NavItem $item) => $item->group === $key));

            if ($items) {
                $entries[] = new NavGroup($key, $group['label'], $group['order'], $items);
            }
        }

        return $this->sorted($entries);
    }

    /**
     * The items in the menu under the user's name, in order.
     *
     * @return list<NavItem>
     */
    public function userItemsFor(?Authenticatable $user): array
    {
        return $this->sorted($this->visible($this->userItems, $user));
    }

    /**
     * @template T of NavItem|NavTool
     *
     * @param  array<string, T>  $items
     * @return list<T>
     */
    private function visible(array $items, ?Authenticatable $user): array
    {
        return array_values(array_filter($items, fn (NavItem|NavTool $item) => Route::has($item->route)
            && ($item->can === null || ($user instanceof Authorizable && $user->can($item->can)))
            && ($item->visible === null || ($item->visible)($user))));
    }

    /**
     * @template T of NavItem|NavGroup
     *
     * @param  list<T>  $entries
     * @return list<T>
     */
    private function sorted(array $entries): array
    {
        usort($entries, fn ($a, $b) => [$a->order, $a->label] <=> [$b->order, $b->label]);

        return $entries;
    }
}
