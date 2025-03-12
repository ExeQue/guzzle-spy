<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\RequestInterface;
use Tests\Support\ClientFactory;
use Tests\Support\RequestFactory;
use Tests\Support\ResponseFactory;
use Tests\Support\TransferStatsFactory;

abstract class TestCase extends BaseTestCase
{
    public function client(): ClientFactory
    {
        return new ClientFactory();
    }

    public function response(): ResponseFactory
    {
        return new ResponseFactory();
    }

    public function request(): RequestFactory
    {
        return new RequestFactory();
    }

    public function transferStats(RequestInterface $request): TransferStatsFactory
    {
        return new TransferStatsFactory($request);
    }
}
