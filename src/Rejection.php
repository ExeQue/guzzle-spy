<?php

declare(strict_types=1);

namespace ExeQue\Guzzle\Spy;

use GuzzleHttp\Psr7\MessageTrait;
use LogicException;
use Psr\Http\Message\ResponseInterface;

class Rejection implements ResponseInterface
{
    use MessageTrait;

    public function __construct(
        private readonly string $reason = 'Rejection',
        private readonly int    $statusCode = 999,
    ) {
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @codeCoverageIgnore
     */
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        throw new LogicException('Not supported');
    }

    public function getReasonPhrase(): string
    {
        return $this->reason;
    }
}