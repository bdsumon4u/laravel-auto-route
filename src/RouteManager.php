<?php

namespace Hotash\AutoRoute;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

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
     * @var array|string[][]
     */
    protected $mapHttpMethods = [
        'index' => ['GET', 'HEAD'],
        'create' => ['GET', 'HEAD'],
        'store' => ['POST'],
        'show' => ['GET', 'HEAD'],
        'edit' => ['GET', 'HEAD'],
        'update' => ['PUT', 'PATCH'],
        'destroy' => ['DELETE'],
    ];

    /**
     * @var string[]
     */
    protected $mapPatterns = [
        'any' => '([^/]+)',
        'int' => '(\d+)',
        'float' => '[+-]?([0-9]*[.])?[0-9]+',
        'bool' => '(true|false|1|0)',
    ];

    /**
     * AutoRoute constructor.
     *
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
        $config = $app['config']['auto-route'];
        $this->mapHttpMethods = array_merge($this->mapHttpMethods, $config['methods']);
        $this->mapPatterns = array_merge($this->mapPatterns, $config['patterns']);
    }

    /**
     * Generate everything for a controller.
     *
     * @param array|string $controller
     * @param array $options
     * @throws \ReflectionException
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
     * @throws \ReflectionException
     */
    public function route(string $prefix, string $controller, array $options = []): void
    {
        $patterns = $options['patterns'] ?? [];
        $as = str_replace('/', '.', trim($prefix, '/')) . '.';
        $options = array_merge(compact('prefix', 'as'), $options);

        foreach ($this->getRoutableMethods($controller, $options) as $method) {
            [$httpMethods, $routeName] = $this->getHttpMethodsAndRouteName($method);
            [$endpoint, $patterns] = $this->getEndpointAndPatterns($method, $patterns);

            if (!isset($this->mapHttpMethods[$routeName]) || in_array($method->name, ['create', 'edit'])) {
                $endpoint .= "/{$routeName}";
            }

            $this->app['router']->group($options,
                function () use ($endpoint, $routeName, $method, $httpMethods, $patterns) {
                    $this->app['router']->addRoute($httpMethods, $endpoint, [$method->class, $method->name])
                        ->where($patterns)
                        ->name($routeName);
                }
            );
        }
    }

    /**
     * Get routable methods of a controller.
     *
     * @param string $controller
     * @param array $options
     * @return array
     * @throws \ReflectionException
     */
    private function getRoutableMethods(string $controller, array $options): array
    {
        $class = new ReflectionClass($controller);

        $only = $options['only'] ?? [];
        $except = $options['except'] ?? [];

        return collect($class->getMethods(ReflectionMethod::IS_PUBLIC))
            ->map(function ($method) use ($controller, $only, $except) {
                if ($method->class !== $controller) {
                    return null;
                }

                if (!empty($only) && !in_array($method->name, $only, true)) {
                    return null;
                }

                if (!empty($except) && in_array($method->name, $except, true)) {
                    return null;
                }

                if ($method->name !== '__invoke' && Str::startsWith($method->name, '__')) {
                    return null;
                }

                return $method;
            })
            ->filter()
            ->toArray();
    }

    /**
     * Get HTTP methods and route name.
     *
     * @param ReflectionMethod $method
     * @return array
     */
    private function getHttpMethodsAndRouteName(ReflectionMethod $method): array
    {
        $methodName = $method->name;
        if ($methods = data_get($this->mapHttpMethods, $methodName)) {
            return [$methods, trim($method->name, '_')];
        }

        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        foreach ($methods as $httpMethod) {
            if (stripos($method->name, strtolower($httpMethod), 0) === 0) {
                $methods = [$httpMethod];
                break;
            }
        }

        return [$methods, strtolower(preg_replace('%([a-z]|\d)([A-Z])%', '\1-\2', lcfirst(
            preg_replace(
                '/' . strtolower($httpMethod) . '_?/i',
                '', trim($methodName, '_'),
                1
            )
        )))];
    }

    /**
     * Get endpoint and route patterns.
     *
     * @param ReflectionMethod $method
     * @param array $patterns
     * @return array
     */
    private function getEndpointAndPatterns(ReflectionMethod $method, array $patterns = []): array
    {
        $routePatterns = $endpoints = [];
        $patterns = array_merge($this->mapPatterns, $patterns);

        foreach ($method->getParameters() as $param) {
            $paramName = $param->getName();
            $typeHint = $param->hasType()
                ? $param->getType()->getName()
                : null;

            if (!$this->isNeededParameter($typeHint, $patterns)) {
                continue;
            }

            $routePatterns[$paramName] = $patterns[$paramName] ??
                ($this->mapPatterns[$typeHint] ?? $this->mapPatterns['any']);
            $endpoints[] = $param->isOptional() ? "{{$paramName}?}" : "{{$paramName}}";
        }

        return [implode('/', $endpoints), $routePatterns];
    }

    /**
     * Is the parameter needed for route pattern.
     *
     * @param $typeHint
     * @param $patterns
     * @return bool
     */
    private function isNeededParameter($typeHint, $patterns): bool
    {
        if (is_null($typeHint)) {
            return true;
        }

        if (class_exists($typeHint)) {
            return is_subclass_of($typeHint, Model::class);
        }

        return in_array($typeHint, ['int', 'float', 'string', 'bool']) || array_key_exists($typeHint, $patterns);
    }
}
