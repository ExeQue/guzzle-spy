<?php

declare(strict_types=1);

namespace Tests\Support\Request;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

trait WithUri
{
    private UriInterface $uri;

    public function uri(string|UriInterface $uri): static
    {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }

        $this->uri = $uri;

        return $this;
    }
}