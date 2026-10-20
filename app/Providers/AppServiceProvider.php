<?php

namespace App\Providers;

use App\Models\Router;
use App\Services\Mikrotik\MikrotikFactory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MikrotikFactory::class);
    }

    public function boot(): void
    {
        // Router list for the sidebar switcher.
        View::composer('layouts.app', function ($view) {
            $view->with('navRouters', Schema::hasTable('routers') ? Router::orderBy('name')->get(['id', 'name', 'is_online']) : collect());
        });
    }
}
