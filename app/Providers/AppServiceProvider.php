<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Modules\Category\Entities\Category;
use Modules\Core\Entities\Permission;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public static $routesAreCached = false;
    public static $configurationIsCached = false;
    public static $runningInConsole = true;
    private static $totalQueries;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        static::$configurationIsCached = $this->app->configurationIsCached();
        static::$routesAreCached = $this->app->routesAreCached();
        static::$runningInConsole = $this->app->runningInConsole();
        if ($this->app->isLocal()) {
//            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
        $this->app->useLangPath(base_path('Modules/Core/Resources/lang')); // change lang path to Core Module
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::defaultView('pagination.default');
        isset($_GET['kk']) &&
        DB::listen(function ($query) {
//            \Log::debug($query->sql);
//            \Log::debug($query->bindings);
            dump($query->sql);
//            dump($query->time);
            dump($query->bindings);
//            dump((debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 35)));
            if (!isset(static::$totalQueries)) {
                static::$totalQueries = 1;
            } else {
                static::$totalQueries += 1;
            }
            echo '<style>body {background: #1a202c}</style>';
            echo '<script>window.total =  ' .static::$totalQueries . ';window.onload = () => alert(window.total) </script>';
        });
    }
}
