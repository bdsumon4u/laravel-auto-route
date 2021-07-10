<?php

namespace Hotash\AutoRoute;

use Illuminate\Support\ServiceProvider;

class AutoRouteServiceProvider extends ServiceProvider
{
    /**
     * Package configuration path.
     *
     * @var string $configPath
     */
    private $configPath = __DIR__ . '/../config/auto-route.php';

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom($this->configPath, 'auto-route');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            $this->configPath => config_path('auto-route.php'),
        ], 'auto-route');

        $this->app->singleton(RouteManager::class, function ($app) {
            return new RouteManager($app);
        });

        $routeManager = $this->app[RouteManager::class];
        $this->app['router']::macro('route', function (string $prefix, string $controller, array $options = []) use ($routeManager) {
            return $routeManager->route($prefix, $controller, $options);
        });
        $this->app['router']::macro('generate', function ($controller, array $options = []) use ($routeManager) {
            return $routeManager->generate($controller, $options);
        });
    }
}
