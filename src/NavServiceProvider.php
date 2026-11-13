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
    }
}
