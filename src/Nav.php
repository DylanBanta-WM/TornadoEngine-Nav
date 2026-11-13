<?php

namespace TornadoEngine\Nav;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;

/**
 * The site's navigation menu. Modules add their items in their service provider's boot():
 *
 *     app(Nav::class)->add('Notes', 'notes.index', active: 'notes.*', order: 50);
 *     app(Nav::class)->add('Reports', 'reports.index', visible: fn ($user) => $user?->is_admin);
 */
class Nav
{
    /** @var array<string, NavItem> */
    private array $items = [];

    /**
     * Adds an item, or replaces the one for the same route.
     *
     * @param  string|null  $active  Route name pattern for the current page; defaults to the route itself.
     * @param  int  $order  Lower numbers come first.
     * @param  Closure(?Authenticatable): bool|null  $visible
     */
    public function add(string $label, string $route, ?string $active = null, int $order = 100, ?Closure $visible = null): static
    {
        $this->items[$route] = new NavItem($label, $route, $active ?? $route, $order, $visible);

        return $this;
    }

    public function remove(string $route): static
    {
        unset($this->items[$route]);

        return $this;
    }

    /**
     * The items this user can see, in order. Items whose route doesn't exist are left out.
     *
     * @return list<NavItem>
     */
    public function for(?Authenticatable $user): array
    {
        $items = array_filter(
            $this->items,
            fn (NavItem $item) => Route::has($item->route) && ($item->visible === null || ($item->visible)($user)),
        );

        usort($items, fn (NavItem $a, NavItem $b) => [$a->order, $a->label] <=> [$b->order, $b->label]);

        return $items;
    }
}
