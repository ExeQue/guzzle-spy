<?php

declare(strict_types=1);

namespace Tests\Support;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Tests\Support\Message\WithContent;
use Tests\Support\Message\WithHeaders;
use Tests\Support\Request\WithMethod;
use Tests\Support\Request\WithUri;

class RequestFactory
{
    use WithContent;
    use WithHeaders;
    use WithMethod;
    use WithUri;

    public function __construct(
        string              $method = 'GET',
        string|UriInterface $uri = 'localhost',
        ?string             $body = null,
        array               $headers = [],
    ) {
        $this
            ->method($method)
            ->uri($uri)
            ->body($body)
            ->headers($headers);
    }

    public function make(): RequestInterface
    {
        return new Request(
            $this->method,
            $this->uri,
            $this->headers,
            $this->body,
        );
    }
}