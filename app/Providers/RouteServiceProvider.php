<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PHPUnit\Framework\Constraint\Callback;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });

        /**
         * @param string $model
         * @param array $middleware
         * @param Closure|string $callback
         * TODO Powered by Komeyl :)
         */
        Route::macro( 'superGroup', function ($model, $callback, $middleware = null) {
            $middleware = is_null($middleware) ? ['auth:' . $model . '-api'] : $middleware;

            $attribute = [
                'prefix' => $model,
                'as' => $model . '.',
                'namespace' => ucfirst($model),
                'middleware' => $middleware,
            ];

            Router::group($attribute , $callback);
        });

        Route::macro('permissionResource', function ($model, $controller, $options = []) {
            $guardName = $options['guard_name'] ?? 'admin-api';
            $methods = ['index', 'show', 'store', 'update', 'destroy'];
            if (isset($options['except'])) {
                foreach ($options['except'] as $except) {
                    unset($methods[$except]);
                }
            }
            if (isset($options['only'])) {
                $methods = [];
                foreach ($options['only'] as $only) {
                    $methods[] = $only;
                }
            }
            foreach ($methods as $method) {
                $realMethod = match($method) {
                    'index', 'show' => 'get',
                    'store' => 'post',
                    'update' => 'put',
                    'destroy' => 'delete'
                };
                $action = match($method) {
                    'index', 'show' => 'read',
                    'store' => 'write',
                    'update' => 'modify',
                    'destroy' => 'delete'
                };
                $url = match($method) {
                    'index', 'store' => $model,
                    'update','destroy', 'show' => $model . "/{" . str_replace('-', '_', Str::singular($model)) . '}',
                };
                Route::name($model . '.' . $method)->{$realMethod}($url, $controller . '@' . $method)
                ->middleware('permission:' . $action . '_' . ($options['permission_name'] ?? Str::singular($model)) . ',' . $guardName);
            }
        });
        \Illuminate\Routing\Route::macro( 'hasPermission', function ($permission, $guardName = 'admin-api') {
            Route::middleware('permission:' . $permission . ',' . $guardName);
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('sms', function (Request $request) {
            return Limit::perMinute(5)
                ->by(optional($request->user())->id ?: $request->ip())
                ->response(function() use ($request) {
                    Log::debug('تلاش مشکوک برای دریافت توکن ورود',[$request->ip(),$request->toArray()]);
                    return new Response('what are you doing !? 🤨');
                });
        });

    }
}
