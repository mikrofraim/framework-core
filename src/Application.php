<?php

declare(strict_types=1);

namespace Mikrofraim;

class Application extends \Mikrofraim\Container\ApplicationContainer
{
    public static function env($key, $default = null): string|bool
    {
        if (!isset($_ENV[$key])) {
            return $default;
        }

        $value = $_ENV[$key];

        if (\mb_strtolower($value) === 'true' || \mb_strtolower($value) === 'false') {
            $value = \filter_var($value, \FILTER_VALIDATE_BOOL);
        }

        return $value;
    }
}
