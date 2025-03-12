<?php

declare(strict_types=1);

namespace Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;

class ClientFactory
{
    private array $responses = [];

    private array $middlewares = [];

    private mixed $handler = null;

    public function responses(callable|array|ResponseInterface $callback): static
    {
        $responses = match (true) {
            $callback instanceof ResponseInterface,
            is_array($callback) => $callback,
            default => $callback(),
        };

        if (is_array($responses) === false) {
            $responses = [$responses];
        }

        $this->responses = [...$this->responses, ...$responses];

        return $this;
    }

    public function handler(callable $handler): static
    {
        $this->handler = $handler;

        return $this;
    }

    public function middleware(callable $middleware, ?string $name = null): static
    {
        if ($name !== null) {
            $this->middlewares[$name] = $middleware;
        } else {
            $this->middlewares[] = $middleware;
        }

        return $this;
    }

    public function make(): Client
    {
        $stack = $this->handler ?? new HandlerStack(
            new MockHandler($this->responses)
        );

        foreach ($this->middlewares as $name => $middleware) {
            $stack->unshift($middleware, is_string($name) ? $name : '');
        }

        return new Client(
            [
                'handler' => $stack,
            ],
        );
    }
}