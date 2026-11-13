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

    public function remove(string $route): static
    {
        unset($this->items[$route], $this->userItems[$route]);

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
     * @param  array<string, NavItem>  $items
     * @return list<NavItem>
     */
    private function visible(array $items, ?Authenticatable $user): array
    {
        return array_values(array_filter($items, fn (NavItem $item) => Route::has($item->route)
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
