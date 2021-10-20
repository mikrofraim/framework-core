<?php

declare(strict_types=1);

namespace Mikrofraim\Http;

abstract class Controller
{
    public function __construct(
        protected \Psr\Http\Message\ServerRequestInterface $request,
    ) {
    }
}
