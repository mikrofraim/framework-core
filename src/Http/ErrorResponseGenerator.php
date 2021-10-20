<?php

declare(strict_types=1);

namespace Mikrofraim\Http;

use Laminas\Escaper\Escaper;
use Laminas\Stratigility\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class ErrorResponseGenerator
{
    private bool $isDevelopmentMode;

    public function __construct(bool $isDevelopmentMode = false)
    {
        $this->isDevelopmentMode = $isDevelopmentMode;
    }

    /**
     * Create/update the response representing the error.
     */
    public function __invoke(
        Throwable $e,
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $response = $response->withStatus(Utils::getStatusCode($e, $response));
        $body = $response->getBody();

        if ($this->isDevelopmentMode) {
            $escaper = new Escaper();
            $body->write(\sprintf(
                '<strong>HTTP %d %s</strong><pre>%s</pre>%s',
                $response->getStatusCode(),
                $response->getReasonPhrase(),
                $escaper->escapeHtml((string) $e),
                \PHP_EOL,
            ));
        } else {
            $body->write(\sprintf(
                '%s%s',
                ($response->getReasonPhrase() ?: 'Unknown Error'),
                \PHP_EOL,
            ));
        }

        return $response;
    }
}
