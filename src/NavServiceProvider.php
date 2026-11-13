<?php

namespace TornadoEngine\Nav;

use Illuminate\Support\ServiceProvider;

class NavServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Nav::class);
    }

    public function boot(Nav $nav): void
    {
        // Core's layout shows nav::bar when this module is installed.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'nav');

        $nav->add(__('Dashboard'), 'dashboard', order: 0);

        // Core 0.2 and later have an Administration area.
        $nav->add(__('Administration'), 'administration', active: 'administration*', order: 1000,
            visible: fn ($user) => (bool) $user?->is_admin);
    }
}
