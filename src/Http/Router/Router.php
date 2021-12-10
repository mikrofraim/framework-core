<?php

declare(strict_types=1);

namespace Mikrofraim\Http\Router;

abstract class Router
{
    abstract public function collectRoutes();

    /* @todo better type */
    abstract public function dispatch(string $method, string $path): mixed;
}
