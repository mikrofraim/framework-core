<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Mikrofraim\Container\Container;
use Mikrofraim\Http\Middleware;
use Mikrofraim\Service\Autowire\Autowire;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class AutoRoute extends Middleware implements MiddlewareInterface
{
    public function __construct(
        private Autowire $autowire
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
            directory: \dirname(__DIR__).'/AutoRoute',
            method: 'handle'
        );

        $router = $autoRoute->getRouter();

        try {
            $route = $router->route($method, $uri);
        } catch (\Exception $e) {
            exit((string) $e);
        }

        if ($route->error) {
            $response = null;

            switch ($route->error) {
                case \AutoRoute\Exception\InvalidArgument::class:
                    $response = new Response(400);
                    $response->getBody()->write('HTTP 400 Bad Request'.PHP_EOL);

                    break;

                case \AutoRoute\Exception\NotFound::class:
                case \AutoRoute\Exception\MethodNotAllowed::class:
                    // $response = new Response($stream, 404);
                    // $response->getBody()->write('HTTP 404 Not Found'.PHP_EOL);

                    return $handler->handle($request);

                    break;

                default:
                    if ($route->exception instanceof Throwable) {
                        throw $route->exception;
                    }
                    $response = new Response(500);
                    $response->getBody()->write('HTTP 500 Internal Server Error'.PHP_EOL);

                    break;
            }

            return $response;
        }

        $dependencies = $this->autowire->resolveDependencies($route->class, '__construct', [
            new Container([
                'Psr\Http\Message\ServerRequestInterface' => $request,
            ]),
        ]);

        $controller = new $route->class(...$dependencies);

        return $controller->{$route->method}(...$route->arguments);
    }
}
