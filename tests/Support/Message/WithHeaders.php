<?php

declare(strict_types=1);

namespace Tests\Support\Message;

trait WithHeaders
{
    private array $headers = [];

    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function headers(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }
}