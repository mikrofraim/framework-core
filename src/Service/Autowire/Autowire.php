<?php

declare(strict_types=1);

namespace Mikrofraim\Service\Autowire;

use Psr\Container\ContainerInterface;

class Autowire
{
    /**
     * Containers holding dependencies, such as the main ApplicationContainer.
     * Must implement PSR-11 ContainerInterface.
     *
     * @var array<\Psr\Container\ContainerInterface>
     */
    private array $containers = [];

    /**
     * Add a PSR-11 container to containers.
     */
    public function addContainer(ContainerInterface $container): void
    {
        $this->containers[] = $container;
    }

    /**
     * Resolve all dependencies for a class using available containers and any
     * extra service provided in $extra.
     */
    public function resolveDependencies(
        string|callable $class,
        array $extra = [],
        string $method = '__construct',
    ): array {
        $params = [];

        if (\is_callable($class) === true) { /** @todo @fix hardcoded method name */
            $method = 'createService';
        }

        $parameters = $this->methodParameters($class, $method) ?? [];

        foreach ($parameters as $parameter) {
            $typeHint = $parameter->getType();

            /* look for dependency in available containers */
            $dependency = $this->findInContainers($typeHint->getName(), $extra);

            if (null === $dependency && $typeHint->allowsNull() === false) {
                throw new /*Autowire*/ \Exception('Could not meet required dependency for '
                . $class . ': ' . $typeHint->getName(), );
            }

            $params[] = $dependency;
        }

        return $params;
    }

    /**
     * Return a new instance of a class after successfully resolving all
     * required dependencies using available containers.
     */
    public function instantiateClass(string $class, array $extra = []): object
    {
        return new $class(...$this->resolveDependencies($class, $extra));
    }

    /**
     * Look for a class in the available containers, including any
     * class => object provided in the $extra array.
     *
     * @todo @improve $extra is a bit messy -- make it containers.. something else?
     *
     * @return ?object
     */
    private function findInContainers(string $class, array $extra = []): ?object
    {
        if (isset($extra[$class])) {
            return $extra[$class];
        }

        foreach ($this->containers as $container) {
            if ($container->has($class)) {
                return $container->get($class);
            }
        }

        return null;
    }

    /**
     * Return the parameters of a method.
     */
    private function methodParameters(string|object $classOrObject, ?string $method): ?array
    {
        try {
            if (\is_callable($classOrObject) === true) {
                $reflection = new \ReflectionFunction($classOrObject);
            } else {
                /** @todo @fix ugly hax for missing __constructor() */
                $reflectionClass = new \ReflectionClass($classOrObject);

                if (!$reflectionClass->hasMethod($method)) {
                    return [];
                }
                $reflection = new \ReflectionMethod($classOrObject, $method);
            }
        } catch (\ReflectionException $e) {
            throw new AutowireException('Failed to reflect: ' . $e->getMessage());
        }

        return $reflection->getParameters();
    }
}
