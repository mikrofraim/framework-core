<?php

declare(strict_types=1);

namespace Mikrofraim\ServiceProvider;

use Mikrofraim\Service\Autowire\Autowire;
use Mikrofraim\ServiceProvider;

class AutowireProvider extends ServiceProvider
{
    public function createService(): Autowire
    {
        return new Autowire();
    }
}
