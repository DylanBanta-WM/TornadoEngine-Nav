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

    public function test_administration_is_a_dropdown_for_admins_only(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertContains('Administration', $this->labels(app(Nav::class)->for($admin)));
        $this->assertNotContains('Administration', $this->labels(app(Nav::class)->for(User::factory()->create())));
        $this->assertNotContains('Administration', $this->labels(app(Nav::class)->userItemsFor($admin)));

        // Items in a dropdown are in order too.
        $group = collect(app(Nav::class)->for($admin))->first(fn ($entry) => $entry instanceof NavGroup && $entry->key === 'administration');
        $this->assertSame('Administration', $group->items[0]->label);
    }

    public function test_items_for_missing_routes_are_left_out(): void
    {
        app(Nav::class)->add('Gone', 'no.such.route');

        $this->assertNotContains('Gone', $this->labels(app(Nav::class)->for(null)));
    }

    public function test_the_dashboard_shows_the_tools_each_person_can_open_in_sections(): void
    {
        $this->routes('zz.notes', 'zz.secret', 'zz.attendance');
        Gate::define('zz.secret', fn (User $user) => $user->email === 'boss@school.test');
        app(Nav::class)
            ->tool('Notes', 'zz.notes', 'Write notes.', section: 'Classroom', icon: 'document', color: 'teal')
            ->tool('Attendance', 'zz.attendance', 'Take attendance.', section: 'Classroom', color: 'not-a-color')
            ->tool('Secret', 'zz.secret', 'Only for the boss.', section: 'Office', can: 'zz.secret')
            ->tool('Gone', 'zz.missing', 'No route.');

        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertOk()
            ->assertSeeInOrder(['Classroom', 'Attendance', 'Take attendance.', 'Notes', 'Write notes.'])
            ->assertSee('bg-teal-100', false)
            ->assertDontSee('Secret')
            ->assertDontSee('Gone');

        $this->actingAs(User::factory()->create(['email' => 'boss@school.test']))->get('/dashboard')->assertSeeInOrder(['Classroom', 'Office', 'Secret']);
        $this->assertSame(['Attendance', 'Notes'], $this->labels(app(Nav::class)->toolsFor(User::factory()->create())['Classroom']));
    }

    public function test_people_without_tools_are_told_to_ask_for_access(): void
    {
        // Remove every registered tool, so only the empty state is left.
        $nav = app(Nav::class);
        foreach (app(Nav::class)->toolsFor(User::factory()->create(['is_admin' => true])) as $tools) {
            foreach ($tools as $tool) {
                $nav->remove($tool->route);
            }
        }

        $this->actingAs(User::factory()->create())->get('/dashboard')->assertSee('No tools yet');
    }

    public function test_admins_see_administration_on_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/dashboard')->assertSeeInOrder(['Administration', 'Administration', 'Settings, scheduled jobs']);
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertDontSee('Settings, scheduled jobs');
    }
}
