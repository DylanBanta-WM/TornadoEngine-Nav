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
        $nav->user(__('Administration'), 'administration', active: 'administration*', order: 900, can: 'administration');
        $nav->user(__('Change password'), 'password.edit', order: 950, visible: fn ($user) => $user !== null && $user->password !== null);
        $nav->tool(__('Administration'), 'administration', __('Settings, scheduled jobs, updates, and each module\'s setup.'),
            section: __('Administration'), icon: 'cog', color: 'gray', can: 'administration');
    }
}
