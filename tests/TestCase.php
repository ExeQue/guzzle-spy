<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Tests\Support\ClientFactory;
use Tests\Support\RequestFactory;
use Tests\Support\ResponseFactory;

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
}
