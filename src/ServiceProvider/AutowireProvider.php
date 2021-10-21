<?php

declare(strict_types=1);

namespace Mikrofraim\ServiceProvider;

use Mikrofraim\ServiceProvider;
use Tomrf\Autowire\Autowire;

class AutowireProvider extends ServiceProvider
{
    public string $alias = 'autowire';

    public function createService(): Autowire
    {
        return new Autowire();
    }
}
