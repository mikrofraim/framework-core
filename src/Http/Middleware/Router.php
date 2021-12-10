<?php

declare(strict_types=1);

namespace Mikrofraim\Http\Middleware;

use Mikrofraim\Container\Container;
use Mikrofraim\Http\Middleware;
use Mikrofraim\Http\Router\FastRouteRouter;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Router extends Middleware
{
    public function __construct(
        private \Mikrofraim\ApplicationConfig $config,
        private \Mikrofraim\Service\Autowire\Autowire $autowire,
        private FastRouteRouter $router
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        // collect routes
        $this->router->collectRoutes();

        // dispatch
        $routeInfo = $this->router->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        // handle dispatch results
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
                    new Container([
                        'Psr\Http\Message\ServerRequestInterface' => $request,
                    ]),
                ]);

                $controller = new $class(...$dependencies);

                return $controller->{$method}(...$params);
        }

        // return response
        return $handler->handle($request);
    }
}
