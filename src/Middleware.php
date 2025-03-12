<?php

declare(strict_types=1);

namespace ExeQue\Guzzle\Spy;

use Closure;
use ExeQue\Guzzle\Spy\Contracts\CanCreateRequestIds;
use ExeQue\Guzzle\Spy\Contracts\Spy;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

class Middleware
{
    private array $transferStats = [];

    public function __construct(
        private readonly Spy $spy,
    ) {
    }

    public function __invoke(callable $handler): Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $id = $options[Spy::REQUEST_ID] = $this->resolveRequestId($request, $options);

            Assert::string($id, 'Request ID must be a string. Got: %s');

            $options = $this->handleBefore($id, $request, $options);

            /** @var PromiseInterface $response */
            $response = $handler($request, $options);

            return $this->handleAfter($id, $response, $request, $options);
        };
    }

    private function handleBefore(string $id, RequestInterface $request, array $options): array
    {
        $this->applyTransferStatsHandler($id, $options);

        $this->spy->before(
            $id,
            $request,
            $options
        );

        $request->getBody()->rewind();

        return $options;
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
        $stats = $this->getTransferStats($id) ?? new TransferStats($request);

        $this->spy->after($id, $response, $request, $stats, $options);

        $response->getBody()->rewind();

        return $response;
    }

    private function rejectionHandler(
        string           $id,
        mixed            $rejection,
        RequestInterface $request,
        array            $options
    ): PromiseInterface {
        $stats    = $this->getTransferStats($id) ?? new TransferStats($request);
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

        $this->spy->after($id, $response, $request, $stats, $options);

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

    private function applyTransferStatsHandler(string $id, array &$options): void
    {
        $existing = $options[RequestOptions::ON_STATS] ?? fn() => null;

        $options[RequestOptions::ON_STATS] = function (TransferStats $transferStats) use ($existing, $id) {
            $this->transferStats[$id] = $transferStats;

            $existing($transferStats);
        };
    }

    private function getTransferStats(string $id): ?TransferStats
    {
        $stats = $this->transferStats[$id] ?? null;
        unset($this->transferStats[$id]);

        return $stats;
    }
}