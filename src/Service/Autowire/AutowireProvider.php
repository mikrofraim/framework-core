<?php

declare(strict_types=1);

namespace Mikrofraim\Service\Autowire;

class AutowireProvider extends \Mikrofraim\ServiceProvider
{
    protected string $alias = 'autowire';

    public function createService(): Autowire
    {
        return new Autowire();
    }
}
