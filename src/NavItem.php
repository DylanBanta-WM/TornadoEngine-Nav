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
     * @param  Closure|null  $visible  Given the signed-in user (or null); return false to hide the item.
     */
    public function __construct(
        public string $label,
        public string $route,
        public string $active,
        public int $order,
        public ?Closure $visible,
    ) {}
}
