<?php

declare(strict_types=1);

use ExeQue\Guzzle\Spy\Middleware;
use ExeQue\Guzzle\Spy\Spy;
use Psr\Http\Message\RequestInterface;

arch()->expect(Middleware::class)->toBeInvokable();

test('returns a closure when called', function () {
    $middleware = new Middleware(
        mock(Spy::class),
    );

    $output = $middleware(fn() => '');

    expect($output)->toBeInstanceOf(Closure::class);
});

test('returned closure has correct parameters', function () {
    $middleware = new Middleware(
        mock(Spy::class),
    );

    $output = $middleware(fn() => '');

    $reflector = new ReflectionFunction($output);

    $parameters = $reflector->getParameters();

    expect()
        ->and($parameters)->toHaveCount(2)
        ->and($parameters[0]->getType())->toBeInstanceOf(ReflectionNamedType::class)
        ->and($parameters[0]->getType()?->getName())->toBe(RequestInterface::class)
        ->and($parameters[1]->getType())->toBeInstanceOf(ReflectionNamedType::class)
        ->and($parameters[1]->getType()?->getName())->toBe('array');
})->depends('returns a closure when called');