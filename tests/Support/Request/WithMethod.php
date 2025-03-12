<?php

declare(strict_types=1);

namespace Tests\Support\Request;

trait WithMethod
{
    private string $method;

    public function method(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function get(): static
    {
        return $this->method('GET');
    }

    public function post(): static
    {
        return $this->method('POST');
    }

    public function put(): static
    {
        return $this->method('PUT');
    }

    public function patch(): static
    {
        return $this->method('PATCH');
    }

    public function delete(): static
    {
        return $this->method('DELETE');
    }

    public function options(): static
    {
        return $this->method('OPTIONS');
    }

    public function head(): static
    {
        return $this->method('HEAD');
    }
}