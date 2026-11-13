<?php

namespace TornadoEngine\Nav;

use Closure;

/**
 * One menu item.
 */
final readonly class NavItem
{
    /**
     * @param  string  $active  Route name pattern that marks the item as the current page, e.g. "notes.*".
     * @param  string|null  $can  Permission the user needs to see it (checked with Laravel's $user->can()).
     * @param  string|null  $group  Key of the dropdown it's in, or null for the top level.
     * @param  Closure|null  $visible  Given the signed-in user (or null); return false to hide the item.
     */
    public function __construct(
        public string $label,
        public string $route,
        public string $active,
        public int $order,
        public ?string $can = null,
        public ?string $group = null,
        public ?Closure $visible = null,
    ) {}
}
