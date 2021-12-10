<?php

declare(strict_types=1);

namespace Mikrofraim;

use Mikrofraim\Container\Container;
use Mikrofraim\Container\NotFoundException;
use Mikrofraim\Exception\FrameworkException;
use Mikrofraim\Service\Autowire\Autowire;
use Mikrofraim\ServiceProvider;
use ReflectionClass;
use ReflectionFunction;
use ReflectionObject;

class ServiceContainer extends Container implements \Psr\Container\ContainerInterface
{
    /**
     * Return a component.
     *
     * @throws NotFoundException No entry was found
     */
    public function get(string $id): mixed
    {
        if ($this->has($id) === false) {
            throw new NotFoundException('Not found: ' . $id);
        }

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

    private function getReturnTypeOfObjectMethod(object $object, string $method)
    {
        try {
            $reflectionObject = new ReflectionObject($object);
            $reflectionMethod = $reflectionObject->getMethod($method);
            $returnType = $reflectionMethod->getReturnType();
            $returnTypeName = $returnType->getName();
        } catch (\ReflectionException $e) {
            return null;
        }

        return $returnTypeName;
    }

    public function addServiceProvider(ServiceProvider $serviceProvider, string $serviceCreatorMethodName = 'createService'): void
    {
        $serviceName = $this->getReturnTypeOfObjectMethod(
            $serviceProvider,
            $serviceCreatorMethodName
        );

        if ($serviceName === null) {
            throw new FrameworkException(
                'Unable to get service name from return type of method "'
                . $serviceCreatorMethodName . '" in service provider ' . \get_class($serviceProvider)
            );
        }

        $this->add($serviceName, $serviceProvider);
    }

    public function set(string $id, mixed $value): mixed
    {
        return $this->container[$id] = $value;
    }

    public function add(string $id, mixed $value): mixed
    {
        if ($this->has($id) === true) {
            throw new FrameworkException('Container already contains ' . $id);
        }

        return $this->set($id, $value);
    }

    public function remove(string $id): void
    {
        if ($this->has($id)) {
            unset($this->container[$id]);
        }
    }

    private function resolve($id): mixed
    {
        $item = $this->container[$id];

        if (!\is_callable($item)) {
            return $item;
        }

        $reflection = new ReflectionFunction($item);
        if ($reflection->getNumberOfParameters() === 0) {
            return $item();
        }

        /** @var Autowire */
        $autowire = $this->get(Autowire::class);

        $dependencies = $autowire->resolveDependencies(
            $this->container[$id],
            '__construct',
            [ $this ]
        );

        return $item(
            ...$dependencies,
        );
    }
}
