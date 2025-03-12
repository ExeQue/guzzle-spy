<?php

declare(strict_types=1);

namespace ExeQue\Guzzle\Spy;

use Closure;
use ExeQue\Guzzle\Spy\Contracts\CanCreateRequestIds;
use ExeQue\Guzzle\Spy\Contracts\Spy;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

class Middleware
{
    public function __construct(
        private readonly Spy $spy,
    ) {
    }

    public function __invoke(callable $handler): Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $id = $options[Spy::REQUEST_ID] = $this->resolveRequestId($request, $options);

            Assert::string($id, 'Request ID must be a string. Got: %s');

            $this->handleBefore($id, $request, $options);

            /** @var PromiseInterface $response */
            $response = $handler($request, $options);

            return $this->handleAfter($id, $response, $request, $options);
        };
    }

    private function handleBefore(string $id, RequestInterface $request, array $options): void
    {
        $this->spy->before(
            $id,
            $request,
            $options
        );

        $request->getBody()->rewind();
    }

    private function handleAfter(
        string           $id,
        PromiseInterface $promise,
        RequestInterface $request,
        array            $options
    ): PromiseInterface {
        return $promise
            ->then(
                onFulfilled: fn(ResponseInterface $response) => $this->responseHandler(
                    $id,
                    $response,
                    $request,
                    $options
                ),
                onRejected : fn(mixed $rejection) => $this->rejectionHandler(
                    $id,
                    $rejection,
                    $request,
                    $options
                )
            );
    }

    private function responseHandler(
        string            $id,
        ResponseInterface $response,
        RequestInterface  $request,
        array             $options
    ): ResponseInterface {
        $this->spy->after($id, $response, $request, $options);

        $response->getBody()->rewind();

        return $response;
    }

    private function rejectionHandler(
        string           $id,
        mixed            $rejection,
        RequestInterface $request,
        array            $options
    ): PromiseInterface {
        $response = new Rejection('Unknown error', 999);

        if (is_string($rejection)) {
            $response = new Rejection("Rejected: $rejection", 999);
        }

        if ($rejection instanceof RequestException) {
            $response = $rejection->getResponse();

            if ($response === null) {
                $response = new Rejection('No response: ' . $rejection->getMessage(), 999);
            }
        }

        $this->spy->after($id, $response, $request, $options);

        return Create::rejectionFor($rejection);
    }

    private function resolveRequestId(RequestInterface $request, array $options): string
    {
        if (array_key_exists(Spy::REQUEST_ID, $options)) {
            $id = $options[Spy::REQUEST_ID];

            if (is_string($id) === false && is_callable($id)) {
                $id = $id();
            }

            Assert::string($id, 'Request ID must be a string or a function that resolves to a string. Got: %s');

            return $id;
        }

        if ($this->spy instanceof CanCreateRequestIds) {
            return $this->spy->createRequestId($request, $options);
        }

        return uniqid('guzzle-spy-', true);
    }
}