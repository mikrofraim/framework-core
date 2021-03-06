<?php

declare(strict_types=1);

namespace Mikrofraim\Http;

use Psr\Http\Message\ResponseInterface;

class SapiEmitter
{
    private $termColor = 92;

    public function emit(ResponseInterface $response): bool
    {
        // $this->assertNoPreviousOutput();

        header(sprintf(
            'HTTP/%s %d %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ));

        foreach ($response->getHeaders() as $key => $values) {
            header($key.': '.\implode(', ', $values));
        }

        if (\PHP_SAPI === 'cli') {
            if ($response->getStatusCode() > 399) {
                $this->termColor = 91;
            } elseif ($response->getStatusCode() > 299) {
                $this->termColor = 93;
            }

            $this->echo(\sprintf(
                '* HTTP/%s %d %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase(),
            ));

            $this->echo('* Headers');

            foreach ($response->getHeaders() as $key => $values) {
                $this->echo('*   '.$key.': '.\implode(', ', $values));
            }

            $this->echo('* Body');

            $this->emitBody($response);

            $response->getBody()->rewind();
            $this->echo('* End of response body ('.\mb_strlen($response->getBody()->getContents()).' bytes emitted)');
        } else {
            $this->emitBody($response);
        }

        return true;
    }

    private function echo(...$args): void
    {
        foreach ($args as $arg) {
            echo "\033[".$this->termColor."m {$arg} \033[0m";
        }
        echo \PHP_EOL;
    }

    /**
     * Emit the message body.
     */
    private function emitBody(ResponseInterface $response): void
    {
        echo $response->getBody();
    }
}
