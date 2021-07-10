<?php

namespace Hotash\AutoRoute;

use Illuminate\Container\Container;

class RouteManager
{
    /**
     * @var Container
     */
    protected $app;

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
        //
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
