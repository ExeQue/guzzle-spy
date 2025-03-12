<?php

declare(strict_types=1);

use ExeQue\Guzzle\Spy\Spy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

covers(Spy::class);

it('runs `before` callbacks', function () {
    $inputId      = md5(random_bytes(16));
    $inputRequest = $this->request()->make();

    $hasRun = false;

    $spy = new Spy();
    $spy->onBefore(function (string $id, RequestInterface $request) use ($inputId, $inputRequest, &$hasRun) {
        $hasRun = true;

        expect($id)->toBe($inputId)
            ->and($request)->toBe($inputRequest);
    });

    $spy->before($inputId, $inputRequest, []);

    expect($hasRun)->toBeTrue();
});

it('runs `after` callbacks', function () {
    $inputId       = md5(random_bytes(16));
    $inputRequest  = $this->request()->make();
    $inputResponse = $this->response()->make();

    $hasRun = false;

    $spy = new Spy();
    $spy->onAfter(function (string $id, ResponseInterface $response, RequestInterface $request) use (
        $inputId,
        $inputRequest,
        $inputResponse,
        &$hasRun
    ) {
        $hasRun = true;

        expect($id)->toBe($inputId)
            ->and($response)->toBe($inputResponse)
            ->and($request)->toBe($inputRequest);
    });

    $spy->after($inputId, $inputResponse, $inputRequest, []);
});

it('runs callbacks in order given', function () {
    $inputId       = md5(random_bytes(16));
    $inputRequest  = $this->request()->make();
    $inputResponse = $this->response()->make();

    $expected = [
        'first',
        'second',
        'third',
        'forth',
    ];

    $actualBefore = [];
    $actualAfter  = [];

    $spy = new Spy(
        [
            function () use (&$actualBefore) {
                $actualBefore[] = 'first';
            },
            function () use (&$actualBefore) {
                $actualBefore[] = 'second';
            },
            function () use (&$actualBefore) {
                $actualBefore[] = 'third';
            },
        ],
        [
            function () use (&$actualAfter) {
                $actualAfter[] = 'first';
            },
            function () use (&$actualAfter) {
                $actualAfter[] = 'second';
            },
            function () use (&$actualAfter) {
                $actualAfter[] = 'third';
            },
        ]
    );

    $spy->onBefore(
        function () use (&$actualBefore) {
            $actualBefore[] = 'forth';
        }
    );

    $spy->onAfter(
        function () use (&$actualAfter) {
            $actualAfter[] = 'forth';
        }
    );

    $spy->before($inputId, $inputRequest, []);
    $spy->after($inputId, $inputResponse, $inputRequest, []);

    expect($actualBefore)->toBe($expected)
        ->and($actualAfter)->toBe($expected);
});