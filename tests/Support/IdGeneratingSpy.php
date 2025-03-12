<?php

declare(strict_types=1);

namespace Tests\Support;

use ExeQue\Guzzle\Spy\Contracts\CanCreateRequestIds;
use Psr\Http\Message\RequestInterface;

class IdGeneratingSpy extends TestSpy implements CanCreateRequestIds
{
    public function __construct(
        private string $id
    ) {

    }

    public function createRequestId(RequestInterface $request, array $options): string
    {
        return $this->id;
    }
}