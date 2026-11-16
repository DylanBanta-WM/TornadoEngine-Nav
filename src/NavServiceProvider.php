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
        // Core's layout shows nav::bar, and its dashboard nav::dashboard, when this module is installed.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'nav');

        $nav->add(__('Dashboard'), 'dashboard', order: 0);
        // The Administration dropdown: modules add theirs with add(..., group: 'administration').
        $nav->group('administration', __('Administration'), order: -10);
        $nav->add(__('Administration'), 'administration', active: 'administration*', order: 10, can: 'administration', group: 'administration');
        $nav->tool(__('Administration'), 'administration', __('Settings, scheduled jobs, updates, and each module\'s setup.'),
            section: __('Administration'), icon: 'cog', color: 'gray', can: 'administration');
    }
}
