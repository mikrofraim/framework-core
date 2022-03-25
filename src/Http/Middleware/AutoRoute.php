<?php

declare(strict_types=1);

namespace Mikrofraim\Http\Middleware;

use Mikrofraim\Http\Middleware;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class AutoRoute extends Middleware implements MiddlewareInterface
{
    public function __construct(
        private \Twig\Environment $twig,
        private LoggerInterface $logger,
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();

        $autoRoute = new \AutoRoute\AutoRoute(
            namespace: 'App\\Http\\AutoRoute',
            directory: '../app/Http/AutoRoute',
            method: 'handle'
        );

        $router = $autoRoute->getRouter();
        $route = $router->route($method, $uri);

        if ($route->error) {
            $response = null;

            switch ($route->error) {
                case \AutoRoute\Exception\InvalidArgument::class:
                    $response = new Response(400);
                    $response->getBody()->write('HTTP 400 Bad Request');

                    break;

                case \AutoRoute\Exception\MethodNotAllowed::class:
                    $response = new Response(405);
                    $response->getBody()->write('HTTP 405 Method Not Allowed');

                    break;

                case \AutoRoute\Exception\NotFound::class:
                    return $handler->handle($request);

                default:
                    if ($route->exception instanceof Throwable) {
                        throw $route->exception;
                    }

                    $response = new Response(500);
                    $response->getBody()->write('HTTP 500 Internal Server Error');

                    break;
            }

            return $response;
        }

        $dependencies = [
            $request,
            $this->twig,
            $this->logger,
        ];

        $controller = new $route->class(...$dependencies);

        return $controller->{$route->method}(...$route->arguments);
    }
}
