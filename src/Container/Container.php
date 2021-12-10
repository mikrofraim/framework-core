<?php

declare(strict_types=1);

namespace Mikrofraim\Container;

class Container implements \Psr\Container\ContainerInterface
{
    protected array $container = [];

    public function __construct(array $initialContent = [])
    {
        foreach ($initialContent as $id => $value) {
            $this->set($id, $value);
        }
    }

    public function get(string $id): mixed
    {
        if (!isset($this->container[$id])) {
            throw new NotFoundException('Container does not contain ' . $id);
        }

        return $this->container[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->container[$id]);
    }

    public function set(string $id, mixed $value): mixed
    {
        $this->container[$id] = $value;

        return $value;
    }
}
