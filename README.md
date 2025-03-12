# Guzzle Spy Middleware

This middleware is designed to be used with Guzzle. It allows you to spy on requests and responses made by Guzzle. This
is useful for testing and logging.

It is a simple middleware that reports request and response information to a spy class.

## Installation

You can install Guzzle Spy Middleware using Composer:

```bash
composer require exeque/guzzle-spy-middleware
```

## Usage

### Basic Usage

Guzzle Spy Middleware can be used by adding it to a Guzzle client.

```php
use ExeQue\Guzzle\Spy\Middleware;
use ExeQue\Guzzle\Spy\Spy;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

$middleware = new Middleware(
    $spy = new Spy(
        fn(
            string $id, 
            RequestInterface $request, 
            array $options,
        ) =>  ..., // Before the request is sent
        fn(
            string $id, 
            ResponseInterface $response, 
            RequestInterface $request, 
            array $options,
        ) => ..., // After the response is received
    ),
);
// or
$middleware = Spy::middleware(
    fn(
        string $id, 
        RequestInterface $request, 
        array $options,
    ) =>  ..., // Before the request is sent
    fn(
        string $id, 
        ResponseInterface $response, 
        RequestInterface $request, 
        array $options,
    ) => ..., // After the response is received
)

$handler = HandlerStack::create();

// It's recommended to use the spy
// as the last middleware in the stack.
// It will work regardless of where it is placed, 
// but any other middleware that modifies the request or response
// will affect the data that is reported to the spy.
$handler->push($middleware, 'spy');

$client = new Client(['handler' => $handler]);

```

### Custom Spy

You can create a custom spy by implementing the `ExeQue\Guzzle\Spy\Contracts\Spy` interface.

```php
use ExeQue\Guzzle\Spy\Contracts\Spy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class TimeLoggingSpy implements Spy
{
    private array $timers = [];

    public function __construct(
        private \Psr\Log\LoggerInterface $logger,
    ) {}

    public function before(string $id, RequestInterface $request, array $options): void {
        $this->timers[$id] = microtime(true);
    }
    
    public function after(string $id, ResponseInterface $response, RequestInterface $request, array $options): void {
        $time = round(microtime(true) - $this->timers[$id], 4);
        unset($this->timers[$id]);
        
        $uri = "{$request->getUri()->getHost()}/{$request->getUri()->getPath()}";
        
        $message = "{$request->getMethod()}@{$uri} Request took {$time} seconds";
        
        $this->logger->info($message);
    }
}
```
If you need more fine-grained control over the request id, you can implement the `ExeQue\Guzzle\Spy\Contracts\CanCreateRequestIds` interface.

The `createRequestId` method is called before the request is sent. It is passed the request and options array, and it should return a unique identifier for the request.

```php
use ExeQue\Guzzle\Spy\Contracts\CanCreateRequestIds;
use ExeQue\Guzzle\Spy\Contracts\Spy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Ramsey/Uuid/Uuid;

class CustomSpy implements Spy, CanCreateRequestIds
{
    public function before(string $id, RequestInterface $request, array $options): void {
        ...
    }
    
    public function after(string $id, ResponseInterface $response, RequestInterface $request, array $options): void {
        ...
    }
    
    public function createRequestId(RequestInterface $request, array $options): string {
        return (string)Uuid::uuid4();
    }
}
```