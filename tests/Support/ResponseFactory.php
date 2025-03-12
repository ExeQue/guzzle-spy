<?php

declare(strict_types=1);

namespace Tests\Support;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\Message\WithContent;
use Tests\Support\Message\WithHeaders;
use Tests\Support\Response\WithStatusCodes;

class ResponseFactory
{
    use WithStatusCodes;
    use WithContent;
    use WithHeaders;

    public function __construct(
        int     $status = 200,
        ?string $body = null,
        array   $headers = [],
    ) {
        $this
            ->status($status)
            ->body($body)
            ->headers($headers);
    }

    public function make(): ResponseInterface
    {
        return new Response(
            $this->status,
            $this->headers,
            $this->body,
        );
    }
}