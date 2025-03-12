<?php

declare(strict_types=1);

use ExeQue\Guzzle\Spy\Data\RequestData;
use ExeQue\Guzzle\Spy\Data\ResponseData;
use ExeQue\Guzzle\Spy\Middleware;
use ExeQue\Guzzle\Spy\Rejection;
use ExeQue\Guzzle\Spy\Spy;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\RejectionException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\IdGeneratingSpy;
use Tests\Support\TestSpy;

it('snitches correctly', function () {
    $middleware = new Middleware(
        $spy = new TestSpy(),
    );

    $client = $this
        ->client()
        ->responses([
            $this->response()->body('fizz buzz')->make(),
            $this->response()->notFound()->body('biz baz')->make(),
        ])
        ->middleware($middleware)
        ->make();

    $client->get('foo.bar');
    $client->get('fizz.buzz');

    $spy
        ->requests(
            function (array $requests) {
                expect($requests)->toHaveCount(2);

                /** @var array{request: RequestInterface, options: array} $success */
                $success = array_shift($requests);
                expect((string)$success['request']->getUri())->toBe('foo.bar');

                /** @var array{request: RequestInterface, options: array} $failed */
                $failed = array_shift($requests);
                expect((string)$failed['request']->getUri())->toBe('fizz.buzz');
            }
        )
        ->responses(
            function (array $responses) {
                expect($responses)->toHaveCount(2);

                /** @var array{response: ResponseInterface, request: RequestInterface, options: array} $success */
                $success = array_shift($responses);
                expect((string)$success['response']->getBody())->toBe('fizz buzz');

                /** @var array{response: ResponseInterface, request: RequestInterface, options: array} $failed */
                $failed = array_shift($responses);
                expect((string)$failed['response']->getBody())->toBe('biz baz')
                    ->and($failed['response']->getStatusCode())->toBe(404);
            }
        );
});

it('spies correctly with http_errors enabled', function () {
    $middlewareBeforeErrors = new Middleware(
        $spyBefore = new TestSpy(),
    );
    $middlewareAfterErrors  = new Middleware(
        $spyAfter = new TestSpy(),
    );

    $stack = MockHandler::createWithMiddleware([
        $this->response()->body('fizz buzz')->make(),
        $this->response()->notFound()->body('biz baz')->make(),
    ]);

    $stack->unshift($middlewareBeforeErrors);
    $stack->push($middlewareAfterErrors);

    $client = $this
        ->client()
        ->handler($stack)
        ->make();

    $client->get('foo.bar');
    expect(fn() => $client->get('fizz.buzz'))->toThrow(RequestException::class);

    foreach ([$spyBefore, $spyAfter] as $spy) {
        $spy
            ->requests(
                function (array $requests) {
                    expect($requests)->toHaveCount(2);

                    /** @var array{request: RequestInterface, options: array} $success */
                    $success = array_shift($requests);
                    expect((string)$success['request']->getUri())->toBe('foo.bar');

                    /** @var array{request: RequestInterface, options: array} $failed */
                    $failed = array_shift($requests);
                    expect((string)$failed['request']->getUri())->toBe('fizz.buzz');
                }
            )
            ->responses(
                function (array $responses) {
                    expect($responses)->toHaveCount(2);

                    /** @var array{response: ResponseInterface, request: RequestInterface, options: array} $success */
                    $success = array_shift($responses);
                    expect((string)$success['response']->getBody())->toBe('fizz buzz');

                    /** @var array{response: ResponseInterface, request: RequestInterface, options: array} $failed */
                    $failed = array_shift($responses);
                    expect((string)$failed['response']->getBody())->toBe('biz baz')
                        ->and($failed['response']->getStatusCode())->toBe(404);
                }
            );
    }
});

it('handles simple rejections from the handler', function () {
    $reason = hash('md5', random_bytes(16));

    $stack = new HandlerStack(
        fn() => Create::rejectionFor($reason)
    );

    $stack->push(
        new Middleware(
            $spy = new TestSpy()
        )
    );

    $client = new Client([
        'handler' => $stack,
    ]);

    expect(fn() => $client->get('foo.bar'))->toThrow(RejectionException::class);

    $spy->responses(function (array $responses) use ($reason) {
        expect($responses)->toHaveCount(1);

        /** @var array{response: ResponseInterface, request: RequestInterface, options: array} $response */
        $response = array_shift($responses);

        expect($response['response'])->toBeInstanceOf(Rejection::class)
            ->and($response['response']->getStatusCode())->toBe(999)
            ->and($response['response']->getReasonPhrase())->toBe("Rejected: $reason");

    });
});

it('handles request exceptions without a response', function () {
    $middleware = new Middleware(
        $spy = new TestSpy(),
    );

    $client = $this
        ->client()
        ->responses([
            fn() => new RequestException('foo bar', $this->request()->make()),
        ])
        ->middleware($middleware)
        ->make();

    expect(fn() => $client->get('foo.bar'))->toThrow(RequestException::class);

    $spy->responses(function (array $responses) {
        expect($responses)->toHaveCount(1);

        /** @var array{response: ResponseInterface, request: RequestInterface, options: array} $response */
        $response = array_shift($responses);
        expect($response['response'])->toBeInstanceOf(Rejection::class)
            ->and($response['response']->getReasonPhrase())->toBe('No response: foo bar')
            ->and($response['response']->getStatusCode())->toBe(999);
    });
});

it('can overwrite request id using request options', function (mixed $request_id, string $expected) {
    $middleware = new Middleware(
        $spy = new TestSpy(),
    );

    $client = $this
        ->client()
        ->responses([
            $this->response()->make(),
        ])
        ->middleware($middleware)
        ->make();

    $client->get('foo.bar', [
        Spy::REQUEST_ID => $request_id,
    ]);

    $spy->responses(function (array $responses) use ($expected) {
        expect($responses)->toHaveCount(1)
            ->and($responses)->toHaveKey($expected);
    });
})->with([
    'string'   => [
        'request_id' => 'custom-id',
        'expected'   => 'custom-id',
    ],
    'callback' => fn() => [
        'request_id' => fn() => 'custom-id',
        'expected'   => 'custom-id',
    ],
]);

it('throws an exception if the request id is not a string', function (mixed $request_id, string $expected) {
    $middleware = new Middleware(
        new TestSpy(),
    );

    $client = $this
        ->client()
        ->responses([
            $this->response()->make(),
        ])
        ->middleware($middleware)
        ->make();

    expect(fn() => $client->get('foo.bar', [Spy::REQUEST_ID => $request_id]))
        ->toThrow(InvalidArgumentException::class, "Got: $expected");
})->with([
    'integer'          => [
        'request_id' => 123,
        'expected'   => 'integer',
    ],
    'array'            => [
        'request_id' => [],
        'expected'   => 'array',
    ],
    'object'           => [
        'request_id' => new stdClass(),
        'expected'   => 'stdClass',
    ],
    'invalid callback' => fn() => [
        'request_id' => fn() => [],
        'expected'   => 'array',
    ],
]);

it('uses the spy to create request ids if the spy supports it', function () {
    $spy = new IdGeneratingSpy(
        $id = md5(random_bytes(16))
    );

    $middleware = new Middleware($spy);

    $client = $this
        ->client()
        ->responses([
            $this->response()->make(),
        ])
        ->middleware($middleware)
        ->make();

    $client->get('foo.bar');

    $spy->requests(function (array $requests) use ($id) {
        expect($requests)->toHaveCount(1)
            ->and($requests)->toHaveKey($id)
            ->and($requests[$id]['options'])->toHaveKey(Spy::REQUEST_ID)
            ->and($requests[$id]['options'][Spy::REQUEST_ID])->toBe($id);
    });
});