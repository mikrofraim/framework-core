<?php

declare(strict_types=1);

namespace Mikrofraim\Container;

class NotFoundException extends \Exception implements \Psr\Container\NotFoundExceptionInterface
{
}
