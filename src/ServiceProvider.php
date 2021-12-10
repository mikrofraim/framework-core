<?php

declare(strict_types=1);

namespace Mikrofraim;

class ServiceProvider
{
    /**
     * @todo InjectionServiceProvider with same autowiring as middleware/controller ?
     */
    public function __construct(
        protected ?ApplicationConfig $config = null,
    ) {
    }
}
