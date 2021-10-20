<?php

declare(strict_types=1);

namespace Mikrofraim\Http\Middleware;

use Laminas\Diactoros\Response;
use Mikrofraim\Http\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NotFound extends Middleware
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $response = new Response();

        $response = $response->withStatus(404);
        $response->getBody()->write('HTTP 404 Not Found' . \PHP_EOL);

        return $response;
    }
}
