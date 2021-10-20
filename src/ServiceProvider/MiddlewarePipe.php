<?php

declare(strict_types=1);

namespace Mikrofraim\ServiceProvider;

use Mikrofraim\ServiceProvider;

class MiddlewarePipe extends ServiceProvider
{
    public string $alias = 'middlewarePipe';

    public function createService(): \Laminas\Stratigility\MiddlewarePipe
    {
        return new \Laminas\Stratigility\MiddlewarePipe();
    }
}
