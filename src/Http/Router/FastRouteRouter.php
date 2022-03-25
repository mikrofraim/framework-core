<?php

declare(strict_types=1);

namespace Mikrofraim\Http\Router;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

class FastRouteRouter
{
    // @var Dispatcher
    private Dispatcher $dispatcher;
    private array $configRoutes;

    public function __construct(
        private string $routesFile
    ) {
        $this->configRoutes = require $routesFile;
    }

    public function collectRoutes()
    {
        $configRoutes = $this->configRoutes;

        $collectRoutesFromConfig = function ($routeCollector, $configRoutes) {
            $this->collectRoutesFromConfig($routeCollector, $configRoutes);
        };

        $this->dispatcher = \FastRoute\simpleDispatcher(
            static function (
                RouteCollector $routeCollector
            ) use ($configRoutes, $collectRoutesFromConfig): void {
                $collectRoutesFromConfig($routeCollector, $configRoutes);
            }
        );
    }

    public function dispatch(string $method, string $path): array
    {
        return $this->dispatcher->dispatch($method, $path);
    }

    private function collectRoutesFromConfig(RouteCollector $routeCollector, array $configRoutes): void
    {
        foreach ($configRoutes as $prefix => $group) {
            foreach ($group['routes'] as $pattern => $route) {
                foreach ($route['methods'] as $requestMethod => $controllerMethod) {
                    $routeCollector->addRoute(
                        \mb_strtoupper($requestMethod),
                        ('/' !== $prefix ? $prefix : '').$pattern,
                        [$route['controller'], $controllerMethod],
                    );
                }
            }
        }
    }
}
