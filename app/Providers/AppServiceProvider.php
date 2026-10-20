<?php

namespace App\Providers;

use App\Models\AlertEvent;
use App\Models\Router;
use App\Services\Config\ConfigExporter;
use App\Services\Config\SshConfigExporter;
use App\Services\Mikrotik\MikrotikFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MikrotikFactory::class);
        $this->app->bind(ConfigExporter::class, SshConfigExporter::class);
    }

    public function boot(): void
    {
        // Sidebar data: the routers this user may see, and how many alerts are firing on them.
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();
            $routers = Schema::hasTable('routers') && $user
                ? Router::visibleTo($user)->orderBy('name')->get(['id', 'name', 'is_online'])
                : collect();

            // Alerts only count on devices this person may look at (for staff: the ones they have signed in to).
            $watched = $user?->permittedRouterIds('monitor.view');
            $alertRouters = $watched === null ? $routers->pluck('id') : $routers->pluck('id')->intersect($watched);

            $view->with('navRouters', $routers);
            $view->with('firingAlerts', Schema::hasTable('alert_events') && $alertRouters->isNotEmpty()
                ? AlertEvent::firing()->whereIn('router_id', $alertRouters)->count()
                : 0);
        });
    }
}
