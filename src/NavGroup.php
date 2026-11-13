<?php

namespace TornadoEngine\Nav;

/**
 * A dropdown in the top bar, with the items this user can see.
 */
final readonly class NavGroup
{
    /** @param  list<NavItem>  $items */
    public function __construct(
        public string $key,
        public string $label,
        public int $order,
        public array $items = [],
    ) {}

    /** @return list<string> */
    public function activePatterns(): array
    {
        return array_map(fn (NavItem $item) => $item->active, $this->items);
    }
}
