<?php

declare(strict_types=1);

namespace Mikrofraim\Container;

use Mikrofraim\Exception\FrameworkException;
use Mikrofraim\ServiceProvider;
use ReflectionClass;

class ApplicationContainer implements \Psr\Container\ContainerInterface
{
    private array $container = [];
    private array $alias = [];

    /**
     * Return a component.
     *
     * @throws NotFoundException No entry was found
     */
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundException('Not found: ' . $id);
        }

        $id = $this->trueId($id);

        if (\is_subclass_of($this->container[$id], ServiceProvider::class)) {
            /* convert service provider into callable service creator */
            $reflection = new ReflectionClass($this->container[$id]);
            $this->container[$id] = $reflection?->getMethod('createService')?->getClosure($this->container[$id]);
        }

        if (\is_callable($this->container[$id])) {
            $this->container[$id] = $this->resolve($id);
        }

        return $this->container[$id];
    }

    /**
     * Returns true if the container can return an entry for the given
     * identifier, false otherwise.
     */
    public function has(string $id): bool
    {
        if (isset($this->container[$id]) || isset($this->alias[$id])) {
            return true;
        }

        return false;
    }

    public function addProvider(ServiceProvider $provider): void
    {
        try {
            $reflection = new ReflectionClass($provider);
        } catch (\Exception $e) {
            throw new \Mikrofraim\Exception\FrameworkException(
                'could not reflect ServiceProvider: ' . $e->getMessage(),
            );
        }

        if ($reflection->hasMethod('createService') === false) {
            throw new \Mikrofraim\Exception\FrameworkException(
                'ServiceProvider does not have createService() method: '
                . $reflection->getName(),
            );
        }

        $serviceClassName = $reflection->getMethod('createService')->
            getReturnType()->getName();

        $this->container[$serviceClassName] = $provider;

        $alias = $provider->getAlias() ?? null;

        if (null !== $alias) {
            $this->alias[$alias] = $serviceClassName;
        }
    }

    public function set(string $id, mixed $value, ?string $alias = null): void
    {
        $this->container[$id] = $value;

        /* assign alias if specified */
        if (null !== $alias) {
            $this->alias[$alias] = $id;
        }
    }

    public function add(string $id, mixed $value, ?string $alias = null): void
    {
        if ($this->has($id) !== null) {
            throw new FrameworkException('Can not add, container already has ' . $id);
        }

        $this->set($id, $value, $alias);
    }

    public function remove(string $id): void
    {
        if ($this->has($id)) {
            unset($this->container[$this->trueId($id)]);
        }
    }

    private function trueId($id): ?string
    {
        if (isset($this->container[$id])) {
            return $id;
        }

        if (isset($this->alias[$id])) {
            return $this->alias[$id];
        }

        return null;
    }

    private function resolve($id): mixed
    {
        $item = $this->container[$this->trueId($id)];

        if (!\is_callable($item)) {
            return $item;
        }

        /** @var \Mikrofraim\Service\Autowire\Autowire */
        $autowire = $this->get(\Mikrofraim\Service\Autowire\Autowire::class);

        $dependencies = $autowire->resolveDependencies(
            $this->container[$this->trueId($id)],
        );

        return $item(
            ...$dependencies,
        );
    }
}
