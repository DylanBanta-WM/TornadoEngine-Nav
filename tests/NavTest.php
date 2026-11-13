<?php

namespace TornadoEngine\Nav\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use TornadoEngine\Nav\Nav;

class NavTest extends TestCase
{
    use RefreshDatabase;

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
        Route::get('/notes', fn () => 'notes')->name('notes.index');
        Route::get('/reports', fn () => 'reports')->name('reports.index');
        Route::getRoutes()->refreshNameLookups();

        app(Nav::class)
            ->add('Reports', 'reports.index', order: 20)
            ->add('Notes', 'notes.index', order: 10);

        $labels = array_map(fn ($item) => $item->label, app(Nav::class)->for(null));

        $this->assertSame(['Dashboard', 'Notes', 'Reports'], $labels);
    }

    public function test_items_can_be_limited_to_some_users(): void
    {
        Route::get('/reports', fn () => 'reports')->name('reports.index');
        Route::getRoutes()->refreshNameLookups();
        app(Nav::class)->add('Reports', 'reports.index', visible: fn ($user) => (bool) $user?->is_admin);

        $admin = User::factory()->create(['is_admin' => true]);
        $staff = User::factory()->create();

        $this->assertContains('Reports', array_map(fn ($item) => $item->label, app(Nav::class)->for($admin)));
        $this->assertNotContains('Reports', array_map(fn ($item) => $item->label, app(Nav::class)->for($staff)));
    }

    public function test_items_for_missing_routes_are_left_out(): void
    {
        app(Nav::class)->add('Gone', 'no.such.route');

        $this->assertNotContains('Gone', array_map(fn ($item) => $item->label, app(Nav::class)->for(null)));
    }
}
