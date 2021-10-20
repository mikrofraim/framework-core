<?php

declare(strict_types=1);

namespace Mikrofraim\ServiceProvider;

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\MiddlewarePipe;
use Mikrofraim\Http\SapiEmitter;
use Mikrofraim\ServiceProvider;

class RequestHandler extends ServiceProvider
{
    public string $alias = 'requestHandler';

    public function createService(MiddlewarePipe $middlewarePipe): RequestHandlerRunner
    {
        return new RequestHandlerRunner(
            $middlewarePipe,
            new SapiEmitter(),
            static function () {
                return ServerRequestFactory::fromGlobals();
            },
            static function (\Throwable $e) {
                $response = (new ResponseFactory())->createResponse(500);
                $response->getBody()->write(\sprintf(
                    'An error occurred: %s',
                    $e->getMessage(),
                ));

                return $response;
            },
        );
    }
}
