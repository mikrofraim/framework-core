<?php

declare(strict_types=1);

namespace Mikrofraim;

use Mikrofraim\Container\Container;
use Mikrofraim\Exception\FrameworkException;

class ApplicationConfig extends Container
{
    public function __construct(array $array = [])
    {
        $this->setFromArray($array);
    }

    public function get(string $id, mixed $default = null): mixed
    {
        return $this->container[$id] ?? $default;
    }

    public function query(string $query)
    {
        $query = \str_replace('\\*', '.*?', \preg_quote($query, '/'));
        $query = \preg_grep('/^' . $query . '$/i', \array_keys($this->container));

        return \array_intersect_key($this->container, \array_flip($query));
    }

    public function setFromArray(array $array, $parent = null): void
    {
        foreach ($array as $key => $value) {
            if (\is_array($value)) {
                $parentKey = $key;

                if ($parent) {
                    $parentKey = $parent . '.' . $key;
                }

                $this->setFromArray($value, $parentKey);
            } else {
                $configKey = $key;

                if ($parent) {
                    $configKey = $parent . '.' . $key;
                }

                $this->set($configKey, $value);
            }
        }
    }

    public function setPhpIni(string $key, mixed $value): void
    {
        try {
            if (\ini_set($key, $value) === false) {
                throw new FrameworkException('ini_set() failed for option: ' . $key);
            }
        } catch (\Exception $e) {
            throw new FrameworkException('Could not set PHP ini option: ' . $key . ': ' . $e->getMessage());
        }
    }

    public function getPhpIni(string $key): string|false
    {
        return \ini_get($key);
    }
}
