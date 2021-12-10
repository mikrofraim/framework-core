<?php

declare(strict_types=1);

namespace Mikrofraim\ServiceProvider;

use Mikrofraim\ServiceProvider;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

class Monolog extends ServiceProvider
{
    public function createService(): \Psr\Log\LoggerInterface
    {
        /* log stream */
        $stream = new StreamHandler($this->config->get('service.monolog.path'));

        /* formatter */
        $formatter = new LineFormatter($this->config->get('service.monolog.format') . \PHP_EOL);
        $stream->setFormatter($formatter);

        /* create logger */
        $logger = new \Monolog\Logger('_');

        /* set timezone */
        $logger->setTimezone(new \DateTimeZone($this->config->get('timezone') ?? 'UTC'));

        /* push stream to logger */
        $logger->pushHandler($stream);

        return $logger;
    }
}
