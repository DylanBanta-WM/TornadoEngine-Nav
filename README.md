# Tornado Engine Nav

A [Tornado Engine](https://github.com/DylanBanta-WM/TornadoEngineCore) core-module: the site's navigation menu. It shows a top bar on larger screens and a menu button on phones.

## Adding a menu item from a module

Require `tornado-engine/nav` in your module's `composer.json`, then add your items in your service provider's `boot()`:

```php
use TornadoEngine\Nav\Nav;

public function boot(Nav $nav): void
{
    $nav->add('Notes', 'notes.index', active: 'notes.*', order: 50);

    // Only for some users:
    $nav->add('Reports', 'reports.index', visible: fn ($user) => (bool) $user?->is_admin);
}
```

Lower `order` numbers come first. `active` is the route name pattern that marks the item as the current page.
