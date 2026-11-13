# Tornado Engine Nav

A [Tornado Engine](https://github.com/DylanBanta-WM/TornadoEngineCore) core-module: the site's navigation menu. It shows a top bar on larger screens and a menu button on phones.

## Adding menu items from a module

Add your items in your service provider's `boot()`:

```php
use TornadoEngine\Nav\Nav;

public function boot(): void
{
    // Only if Nav is installed.
    $this->callAfterResolving(Nav::class, function (Nav $nav) {
        // In the top bar.
        $nav->add('Notes', 'notes.index', active: 'notes.*', order: 50);

        // In a dropdown in the top bar.
        $nav->group('tools', 'Tools', order: 40);
        $nav->add('Inventory', 'inventory.index', group: 'tools');

        // In the menu under the user's name, above Log Out.
        $nav->user('My settings', 'notes.settings');
    });
}
```

- `can` limits an item to users with a permission, checked with Laravel's `$user->can()`: `$nav->add('Reports', 'reports.index', can: 'reports.view')`. A dropdown only shows when the user can see at least one item in it.
- Lower `order` numbers come first. `active` is the route name pattern that marks the item as the current page.
