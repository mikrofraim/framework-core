<?php

declare(strict_types=1);

namespace Mikrofraim\Http\Middleware;

use Mikrofraim\ApplicationConfig;
use Mikrofraim\Http\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class Session extends Middleware
{
    public function __construct(
        private ApplicationConfig $config,
        private LoggerInterface $logger,
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        try {
            if (\PHP_SESSION_NONE === \session_status()) {
                \session_start();
            }
        } catch (\Exception $e) {
            if ($this->config->get('middleware.session.fallback_handler')
                !== \ini_get('session.save_handler')) {
                \ini_set('session.save_handler', $this->config->get('middleware.session.fallback_handler'));
                \ini_set('session.save_path', $this->config->get('middleware.session.fallback_path'));

                /** @todo @fix what if path is not a filesystem path? */
                $directory = $this->config->get('middleware.session.fallback_path');

                if (!\file_exists($directory)) {
                    try {
                        \mkdir($directory);
                    } catch (\Exception $e) {
                        throw new \Exception('could not create directory for fallback session save handler: '
                            .$e->getMessage(), );
                    }
                }

                $this->logger->warning('failed to create session, trying configured fallback handler: '
                    .$this->config->get('middleware.session.fallback_handler'), );

                return $this->process($request, $handler);
            }

            throw new \Exception('could not create session: '.$e->getMessage());
        }

        return $handler->handle($request);
    }
}
