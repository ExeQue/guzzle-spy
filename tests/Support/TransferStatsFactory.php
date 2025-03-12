<?php

declare(strict_types=1);

namespace Tests\Support;

use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class TransferStatsFactory
{
    private ?ResponseInterface $response = null;

    private int|null|float $time = null;

    private array $handlerData = [];

    private mixed $handlerErrorData = null;

    public function __construct(
        private RequestInterface $request,
    ) {
    }

    public function response(ResponseInterface $response): static
    {
        $this->response = $response;

        return $this;
    }

    public function timeSpent(int|float $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function handlerData(array $data)
    {
        $this->handlerData = $data;

        return $this;
    }

    public function handlerErrorData(mixed $errorData): static
    {
        $this->handlerErrorData = $errorData;

        return $this;
    }

    public function make(): TransferStats
    {
        return new TransferStats(
            $this->request,
            $this->response,
            $this->time,
            $this->handlerErrorData,
            $this->handlerData
        );
    }
}