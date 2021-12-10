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
        return $this->queryArray($query, $this->container);
    }

    public function setFromArray(array $array): void
    {
        foreach ($this->flattenArray($array) as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function setPhpIniFromConfig(array $configArray): void
    {
        foreach ($this->flattenArray($configArray) as $key => $value) {
            $this->setPhpIni($key, $value);
        }
    }

    public function setPhpIni(string $key, mixed $value): void
    {
        try {
            if (false === \ini_set($key, $value)) {
                throw new FrameworkException('ini_set() failed for option: '.$key);
            }
        } catch (\Exception $e) {
            throw new FrameworkException('Could not set PHP ini option: '.$key.': '.$e->getMessage());
        }
    }

    public function getPhpIni(string $key): string|false
    {
        return \ini_get($key);
    }

    public static function env($key, $default = null): mixed
    {
        if (!isset($_ENV[$key])) {
            return $default;
        }

        $value = $_ENV[$key];

        if ('true' === \mb_strtolower($value) || 'false' === \mb_strtolower($value)) {
            $value = \filter_var($value, \FILTER_VALIDATE_BOOL);
        }

        return $value;
    }

    private function flattenArray(array $arrayPtr, $parentPtr = null): array
    {
        $flat = [];

        foreach ($arrayPtr as $key => $value) {
            if (\is_array($value)) {
                $parentKey = $key;

                if ($parentPtr) {
                    $parentKey = $parentPtr.'.'.$key;
                }

                $flat = array_merge($flat, $this->flattenArray($value, $parentKey));
            } else {
                $configKey = $key;

                if ($parentPtr) {
                    $configKey = $parentPtr.'.'.$key;
                }

                $flat[$configKey] = $value;
            }
        }

        return $flat;
    }

    private function queryArray(string $query, array $array)
    {
        $query = \str_replace('\\*', '.*?', \preg_quote($query, '/'));
        $query = \preg_grep('/^'.$query.'$/i', \array_keys($array));

        return \array_intersect_key($array, \array_flip($query));
    }
}
