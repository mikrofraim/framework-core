<?php

declare(strict_types=1);

namespace Mikrofraim\Http\Middleware;

use HaydenPierce\ClassFinder\ClassFinder;
use Laminas\Diactoros\Response;
use Mikrofraim\Http\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;

/** @todo @fix this class is a mess */
class Router extends Middleware
{
    public function __construct(
        private \Mikrofraim\ApplicationConfig $config,
        private \Tomrf\Autowire\Autowire $autowire,
        private \Mikrofraim\Routes $routes,
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $classes = ClassFinder::getClassesInNamespace(
            'App\\Http\\Controller',
            ClassFinder::RECURSIVE_MODE,
        );

        $routes = $this->routes;

        $dispatcher = \FastRoute\simpleDispatcher(
            static function (\FastRoute\RouteCollector $routeCollector) use ($classes, $routes): void {
                /* add routes based on attributes */
                foreach ($classes as $class) {
                    $reflection = new ReflectionClass($class);

                    /** @todo @improve do not assume the attribute exists */
                    $routeAttribute = $reflection->getAttributes(\Mikrofraim\Attribute\Route::class)[0] ?? null;

                    if (!$routeAttribute) {
                        continue;
                    }
                    $routeAttributeArguments = $routeAttribute->getArguments();
                    $routeAttributePath = $routeAttributeArguments[0];

                    foreach ($reflection->getMethods() as $method) {
                        $methodAttribute = $method->getAttributes(\Mikrofraim\Attribute\RouteMethod::class)[0] ?? null;

                        if (!$methodAttribute) {
                            continue;
                        }

                        foreach ($methodAttribute->getArguments() as $method) {
                            $routeCollector->addRoute(
                                \mb_strtoupper($method),
                                $routeAttributePath,
                                [$class, $method],
                            );
                        }
                    }
                }

                /* add routes from config */
                foreach ($routes->get('routes') as $prefix => $group) {
                    foreach ($group['routes'] as $pattern => $route) {
                        foreach ($route['methods'] as $requestMethod => $controllerMethod) {
                            $routeCollector->addRoute(
                                \mb_strtoupper($requestMethod),
                                ('/' !== $prefix ? $prefix : '') . $pattern,
                                [$route['controller'], $controllerMethod],
                            );
                        }
                    }
                }
            },
        );

        $routeInfo = $dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath(),
        );

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                $response = new Response();
                $response = $response->withHeader('Allow', \implode(', ', $allowedMethods));

                return $response->withStatus(405);
            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $params = $routeInfo[2];

                $class = $handler[0];
                $method = $handler[1];

                $dependencies = $this->autowire->resolveDependencies($class, '__construct', [
                    'Psr\Http\Message\ServerRequestInterface' => $request,
                ]);

                $controller = new $class(...$dependencies);

                return $controller->{$method}(...$params);
        }

        return $handler->handle($request);
    }
}
