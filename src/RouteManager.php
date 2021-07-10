<?php

namespace Hotash\AutoRoute;

use Illuminate\Container\Container;
use Illuminate\Support\Str;

class RouteManager
{
    /**
     * @var Container
     */
    protected $app;

    /**
     * @var string
     */
    protected $namespace = 'App\\Http\\Controllers';

    /**
     * AutoRoute constructor.
     *
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * Generate everything for a controller.
     *
     * @param array|string $controller
     * @param array $options
     */
    public function generate($controller, array $options = []): void
    {
        if (is_array($controller)) {
            foreach ($controller as $item) {
                $this->generate($item, $options);
            }
        }

        if (!is_string($controller)) {
            return;
        }

        $controllerHint = Str::beforeLast(Str::after($controller, $this->namespace), 'Controller');
        $controllerPath = collect(explode('\\', $controllerHint))->map(function ($item) {
            return Str::kebab($item);
        })->toArray();

        $controllerPath[] = Str::plural(array_pop($controllerPath));
        $this->route(implode('/', $controllerPath), $controller, $options);
    }

    /**
     * Define route for the routable methods of a controller.
     *
     * @param string $prefix
     * @param string $controller
     * @param array $options
     */
    public function route(string $prefix, string $controller, array $options = []): void
    {
        //
    }
}
