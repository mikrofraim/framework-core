<?php

declare(strict_types=1);

namespace Mikrofraim;

class ServiceProvider
{
    protected string $alias;

    /**
     * @todo InjectionServiceProvider with same autowiring as middleware/controller ?
     */
    public function __construct(
        protected ?ApplicationConfig $config = null,
    ) {
    }

    final public function getAlias(): ?string
    {
        return $this->alias ?? null;
    }
}
