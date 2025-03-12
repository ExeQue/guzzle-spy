<?php

declare(strict_types=1);

namespace ExeQue\Guzzle\Spy\Contracts;

use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Spy
{
    public const REQUEST_ID = 'guzzle-spy-id';

    public function before(string $id, RequestInterface $request, array $options): void;

    public function after(string $id, ResponseInterface $response, RequestInterface $request, TransferStats $transferStats, array $options): void;
}