<?php

namespace TornadoEngine\Nav;

use Closure;

/**
 * A card on the dashboard.
 */
final readonly class NavTool
{
    /**
     * @param  string  $icon  The inside of a 24×24 outline SVG (Heroicons), e.g. '<path stroke-linecap="round" … d="…"/>'.
     * @param  string  $color  One of Nav::COLORS.
     */
    public function __construct(
        public string $label,
        public string $route,
        public string $description,
        public string $section,
        public string $icon,
        public string $color,
        public ?string $can = null,
        public ?Closure $visible = null,
    ) {}
}
