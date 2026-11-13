<?php

namespace TornadoEngine\Nav\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use TornadoEngine\Nav\Nav;
use TornadoEngine\Nav\NavGroup;

class NavTest extends TestCase
{
    use RefreshDatabase;

    private function routes(string ...$names): void
    {
        foreach ($names as $name) {
            Route::get('/'.str_replace('.', '/', $name), fn () => $name)->name($name);
        }
        Route::getRoutes()->refreshNameLookups();
    }

    private function labels(array $entries): array
    {
        return array_map(fn ($entry) => $entry->label, $entries);
    }

    public function test_the_site_menu_comes_from_nav(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('aria-label="Menu"', false)
            ->assertSee('Dashboard');
    }

    public function test_modules_add_items_in_order(): void
    {
        $this->routes('notes.index', 'reports.index');

        app(Nav::class)->add('Reports', 'reports.index', order: 20)->add('Notes', 'notes.index', order: 10);

        $this->assertSame(['Dashboard', 'Notes', 'Reports'], $this->labels(app(Nav::class)->for(null)));
    }

    public function test_items_only_show_to_users_with_the_permission(): void
    {
        $this->routes('reports.index');
        Gate::define('reports.view', fn (User $user) => $user->is_admin);
        app(Nav::class)->add('Reports', 'reports.index', can: 'reports.view');

        $this->assertContains('Reports', $this->labels(app(Nav::class)->for(User::factory()->create(['is_admin' => true]))));
        $this->assertNotContains('Reports', $this->labels(app(Nav::class)->for(User::factory()->create())));
        $this->assertNotContains('Reports', $this->labels(app(Nav::class)->for(null)));
    }

    public function test_dropdowns_hold_their_items_and_hide_when_empty(): void
    {
        $this->routes('inventory.index', 'loans.index');
        Gate::define('loans.view', fn (User $user) => $user->is_admin);

        app(Nav::class)
            ->group('tools', 'Tools', order: 40)
            ->add('Inventory', 'inventory.index', group: 'tools')
            ->group('admin-tools', 'Admin tools', order: 50)
            ->add('Loans', 'loans.index', can: 'loans.view', group: 'admin-tools');

        $entries = app(Nav::class)->for(User::factory()->create());
        $tools = collect($entries)->first(fn ($entry) => $entry instanceof NavGroup && $entry->key === 'tools');

        $this->assertSame(['Inventory'], $this->labels($tools->items));
        $this->assertNotContains('Admin tools', $this->labels($entries));
        $this->assertNotContains('Inventory', $this->labels($entries));
    }

    public function test_the_user_menu_has_administration_for_admins_above_log_out(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertContains('Administration', $this->labels(app(Nav::class)->userItemsFor($admin)));
        $this->assertSame([], app(Nav::class)->userItemsFor(User::factory()->create()));
        $this->assertNotContains('Administration', $this->labels(app(Nav::class)->for($admin)));

        $html = $this->actingAs($admin)->get('/dashboard')->getContent();
        $this->assertLessThan(strpos($html, 'Log Out'), strpos($html, 'Administration'));
    }

    public function test_items_for_missing_routes_are_left_out(): void
    {
        app(Nav::class)->add('Gone', 'no.such.route');

        $this->assertNotContains('Gone', $this->labels(app(Nav::class)->for(null)));
    }
}
