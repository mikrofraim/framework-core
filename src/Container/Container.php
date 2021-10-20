<?php

declare(strict_types=1);

namespace Mikrofraim\Container;

class Container implements \Psr\Container\ContainerInterface
{
    protected array $container = [];

    public function get(string $id): mixed
    {
        if (!isset($this->container[$id])) {
            throw new NotFoundException('container does not contain ' . $id);
        }

        return $this->container[$id];
    }

    public function has(string $id): bool
    {
        if (isset($this->container[$id])) {
            return true;
        }

        return false;
    }

    public function set(string $id, mixed $value): mixed
    {
        $this->container[$id] = $value;

        return $value;
    }
}
