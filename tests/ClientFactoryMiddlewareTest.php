<?php

declare(strict_types=1);

namespace Paysera\Component\RestClientCommon\Tests;

use Fig\Http\Message\StatusCodeInterface;
use Generator;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Paysera\Component\RestClientCommon\Client\ApiClient;
use Paysera\Component\RestClientCommon\Middleware\GuzzleMiddlewareProviderInterface;
use Paysera\Component\RestClientCommon\Util\ClientFactoryAbstract;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class ClientFactoryMiddlewareTest extends TestCase
{
    /**
     * @dataProvider middlewareDataProvider
     *
     * @param GuzzleMiddlewareProviderInterface[] $middlewares
     * @param array<string, string> $expectedHeaders
     */
    public function testMiddlewareIsAppliedToRequests(
        array $middlewares,
        array $expectedHeaders
    ): void {
        $mockHandler = new MockHandler([new Response(StatusCodeInterface::STATUS_NO_CONTENT)]);
        $factory = $this->createFactory($mockHandler);

        foreach ($middlewares as $middleware) {
            $factory->addMiddlewareProvider($middleware);
        }

        $client = $this->createTestClient($factory);
        $client->getSomething();

        $request = $mockHandler->getLastRequest();

        foreach ($expectedHeaders as $headerName => $expectedValue) {
            $this->assertTrue($request->hasHeader($headerName));
            $this->assertSame($expectedValue, $request->getHeaderLine($headerName));
        }
    }

    /**
     * @return Generator<string, array{
     *     middlewares: GuzzleMiddlewareProviderInterface[],
     *     expectedHeaders: array<string, string>,
     * }>
     */
    public function middlewareDataProvider(): Generator
    {
        yield 'single middleware adds header' => [
            'middlewares' => [
                $this->createHeaderMiddleware('X-Test', 'test-value'),
            ],
            'expectedHeaders' => [
                'X-Test' => 'test-value',
            ],
        ];
        yield 'multiple middlewares add their headers' => [
            'middlewares' => [
                $this->createHeaderMiddleware('X-First', 'first-value'),
                $this->createHeaderMiddleware('X-Second', 'second-value'),
            ],
            'expectedHeaders' => [
                'X-First' => 'first-value',
                'X-Second' => 'second-value',
            ],
        ];
    }

    public function testNoMiddlewareDoesNotAffectRequests(): void
    {
        $mockHandler = new MockHandler([new Response(StatusCodeInterface::STATUS_NO_CONTENT)]);
        $factory = $this->createFactory($mockHandler);

        $client = $this->createTestClient($factory);
        $client->getSomething();

        $request = $mockHandler->getLastRequest();

        $this->assertFalse($request->hasHeader('X-Test'));
    }

    private function createHeaderMiddleware(string $headerName, string $headerValue): GuzzleMiddlewareProviderInterface
    {
        return new class($headerName, $headerValue) implements GuzzleMiddlewareProviderInterface {
            private string $headerName;
            private string $headerValue;

            public function __construct(string $headerName, string $headerValue)
            {
                $this->headerName = $headerName;
                $this->headerValue = $headerValue;
            }

            public function getMiddleware(): callable
            {
                return function (callable $handler) {
                    return function (RequestInterface $request, array $options) use ($handler) {
                        $request = $request->withHeader($this->headerName, $this->headerValue);
                        return $handler($request, $options);
                    };
                };
            }
        };
    }

    private function createFactory(MockHandler $mockHandler): ClientFactoryAbstract
    {
        return new class($mockHandler) extends ClientFactoryAbstract {
            private MockHandler $mockHandler;

            public function __construct(MockHandler $mockHandler)
            {
                $this->mockHandler = $mockHandler;
            }

            public function buildTestClient(): ApiClient
            {
                return $this->createApiClient([]);
            }

            protected function getHandlerStack()
            {
                return HandlerStack::create($this->mockHandler);
            }
        };
    }

    private function createTestClient(ClientFactoryAbstract $factory): Client\TestClient
    {
        return new Client\TestClient($factory->buildTestClient());
    }
}
