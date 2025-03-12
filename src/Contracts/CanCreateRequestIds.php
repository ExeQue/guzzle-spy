<?php

declare(strict_types=1);

namespace ExeQue\Guzzle\Spy\Contracts;

use Psr\Http\Message\RequestInterface;

interface CanCreateRequestIds
{
    public function createRequestId(RequestInterface $request, array $options): string;
}