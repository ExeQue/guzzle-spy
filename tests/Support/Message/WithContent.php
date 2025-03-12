<?php

declare(strict_types=1);

namespace Tests\Support\Message;

trait WithContent
{
    private ?string $body = null;

    abstract public function header(string $name, string $value): static;

    public function body(?string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function json(array $data): static
    {
        return $this->body(json_encode($data, JSON_THROW_ON_ERROR))->header('Content-Type', 'application/json');
    }
}