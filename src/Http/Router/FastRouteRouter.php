<?php

declare(strict_types=1);

namespace Mikrofraim\Http\Router;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use HaydenPierce\ClassFinder\ClassFinder;

class FastRouteRouter extends Router
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

        $classes = ClassFinder::getClassesInNamespace(
            'App\\Http\\Controller',
            ClassFinder::RECURSIVE_MODE,
        );

        $collectRoutesFromClasses = function ($routeCollectort, $classes) {
            $this->collectRoutesFromClasses($routeCollectort, $classes);
        };

        $collectRoutesFromConfig = function ($routeCollector, $configRoutes) {
            $this->collectRoutesFromConfig($routeCollector, $configRoutes);
        };

        $this->dispatcher = \FastRoute\simpleDispatcher(
            static function (
                RouteCollector $routeCollector
            ) use ($classes, $configRoutes, $collectRoutesFromClasses, $collectRoutesFromConfig): void {
                $collectRoutesFromClasses($routeCollector, $classes);
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

    private function collectRoutesFromClasses(RouteCollector $routeCollector, array $classes): void
    {
        foreach ($classes as $class) {
            $reflection = new \ReflectionClass($class);

            $routeAttribute = $reflection->getAttributes(\Mikrofraim\Attribute\Route::class)[0] ?? null;
            if (!$routeAttribute) {
                continue;
            }

            $routeAttributeArguments = $routeAttribute->getArguments();
            $routeAttributePath = $routeAttributeArguments[0];

            foreach ($reflection->getMethods() as $method) {
                $methodAttribute = $method->getAttributes(\Mikrofraim\Attribute\RouteMethod::class)[0] ?? null;

                if ($methodAttribute) {
                    foreach ($methodAttribute->getArguments() as $method) {
                        $routeCollector->addRoute(
                            \mb_strtoupper($method),
                            $routeAttributePath,
                            [$class, $method],
                        );
                    }
                }
            }

            // foreach class
        }
    }
}
