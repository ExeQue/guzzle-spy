<?php

declare(strict_types=1);

namespace Tests\Support\Response;

trait WithStatusCodes
{
    private int $status = 200;

    public function status(int $code): static
    {
        $this->status = $code;

        return $this;
    }

    public function continue(): static
    {
        return $this->status(100);
    }

    public function switchingProtocols(): static
    {
        return $this->status(101);
    }

    public function ok(): static
    {
        return $this->status(200);
    }

    public function created(): static
    {
        return $this->status(201);
    }

    public function accepted(): static
    {
        return $this->status(202);
    }

    public function noContent(): static
    {
        return $this->status(204);
    }

    public function movedPermanently(): static
    {
        return $this->status(301);
    }

    public function found(): static
    {
        return $this->status(302);
    }

    public function notModified(): static
    {
        return $this->status(304);
    }

    public function badRequest(): static
    {
        return $this->status(400);
    }

    public function unauthorized(): static
    {
        return $this->status(401);
    }

    public function forbidden(): static
    {
        return $this->status(403);
    }

    public function notFound(): static
    {
        return $this->status(404);
    }

    public function methodNotAllowed(): static
    {
        return $this->status(405);
    }

    public function conflict(): static
    {
        return $this->status(409);
    }

    public function internalServerError(): static
    {
        return $this->status(500);
    }

    public function notImplemented(): static
    {
        return $this->status(501);
    }

    public function badGateway(): static
    {
        return $this->status(502);
    }

    public function serviceUnavailable(): static
    {
        return $this->status(503);
    }
}