<?php

declare(strict_types=1);

namespace Tests\Support;

use ExeQue\Guzzle\Spy\Contracts\Spy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class TestSpy implements Spy
{
    /** @var array<string, array{request: RequestInterface, options: array}> */
    private array $requests = [];

    /** @var array<string, array{response: ResponseInterface, request: RequestInterface, options: array}> */
    private array $responses = [];

    public function before(string $id, RequestInterface $request, array $options): void
    {
        $this->requests[$id] = [
            'request' => $request,
            'options' => $options,
        ];
    }

    public function after(string $id, ResponseInterface $response, RequestInterface $request, array $options): void
    {
        $this->responses[$id] = [
            'response' => $response,
            'request'  => $request,
            'options'  => $options,
        ];
    }

    public function requests(callable $callback): static
    {
        $callback($this->requests);

        return $this;
    }

    public function responses(callable $callback): static
    {
        $callback($this->responses);

        return $this;
    }
}