<?php

declare(strict_types=1);

namespace ExeQue\Guzzle\Spy;

use ExeQue\Guzzle\Spy\Contracts\Spy as SpyContract;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Spy implements SpyContract
{
    private array $callbacks = [
        'before' => [],
        'after'  => [],
    ];

    public function __construct(
        array|callable $before = [],
        array|callable $after = [],
    ) {
        if (is_array($before) === false) {
            // @codeCoverageIgnoreStart
            $before = [$before];
            // @codeCoverageIgnoreEnd
        }

        if (is_array($after) === false) {
            // @codeCoverageIgnoreStart
            $after = [$after];
            // @codeCoverageIgnoreEnd
        }

        $this->onBefore(...$before);
        $this->onAfter(...$after);
    }

    public function before(string $id, RequestInterface $request, array $options): void
    {
        $this->runCallbacks('before', $id, $request, $options);
    }

    public function after(string $id, ResponseInterface $response, RequestInterface $request, TransferStats $transferStats, array $options): void
    {
        $this->runCallbacks('after', $id, $response, $request, $transferStats, $options);
    }

    public function onBefore(callable ...$callbacks): static
    {
        return $this->addCallback('before', ...$callbacks);
    }

    public function onAfter(callable ...$callbacks): static
    {
        return $this->addCallback('after', ...$callbacks);
    }

    private function addCallback(string $segment, callable ...$callbacks): static
    {
        $this->callbacks[$segment] = [
            ...$this->callbacks[$segment] ?? [],
            ...$callbacks,
        ];

        return $this;
    }

    private function runCallbacks(string $segment, mixed ...$args): void
    {
        $callbacks = $this->callbacks[$segment] ?? [];

        foreach ($callbacks as $callback) {
            $callback(...$args);
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public static function middleware(
        array|callable $before = [],
        array|callable $after = [],
    ): Middleware {
        return new Middleware(
            new self($before, $after),
        );
    }
}