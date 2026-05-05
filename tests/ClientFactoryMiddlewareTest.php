<?php

declare(strict_types=1);

namespace Paysera\Component\RestClientCommon\Tests;

use Fig\Http\Message\StatusCodeInterface;
use Generator;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Paysera\Component\RestClientCommon\Exception\ClientException;
use Paysera\Component\RestClientCommon\Util\ClientFactoryAbstract;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class ClientFactoryMiddlewareTest extends TestCase
{
    /**
     * @dataProvider middlewareDataProvider
     *
     * @param callable[] $middlewares
     * @param array<string, string> $expectedHeaders
     * @throws ClientException
     */
    public function testMiddlewareIsAppliedToRequests(
        array $middlewares,
        array $expectedHeaders
    ): void {
        $mockHandler = new MockHandler([new Response(StatusCodeInterface::STATUS_NO_CONTENT)]);
        $factory = $this->createFactory($mockHandler);

        foreach ($middlewares as $middleware) {
            $factory->addMiddleware($middleware);
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
     *     middlewares: callable[],
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

    /**
     * @throws ClientException
     */
    public function testNoMiddlewareDoesNotAffectRequests(): void
    {
        $mockHandler = new MockHandler([new Response(StatusCodeInterface::STATUS_NO_CONTENT)]);
        $factory = $this->createFactory($mockHandler);

        $client = $this->createTestClient($factory);
        $client->getSomething();

        $request = $mockHandler->getLastRequest();

        $this->assertFalse($request->hasHeader('X-Test'));
    }

    private function createHeaderMiddleware(string $headerName, string $headerValue): callable
    {
        return function (callable $handler) use ($headerName, $headerValue) {
            return function (RequestInterface $request, array $options) use ($handler, $headerName, $headerValue) {
                $request = $request->withHeader($headerName, $headerValue);
                return $handler($request, $options);
            };
        };
    }

    private function createFactory(MockHandler $mockHandler): ClientFactoryAbstract
    {
        return new class($mockHandler)  extends ClientFactoryAbstract {
            private MockHandler $mockHandler;

            public function __construct(MockHandler $mockHandler)
            {
                $this->mockHandler = $mockHandler;
            }

            protected function getHandlerStack(): HandlerStack
            {
                return HandlerStack::create($this->mockHandler);
            }
        };
    }

    private function createTestClient(ClientFactoryAbstract $factory): Client\TestClient
    {
        return new Client\TestClient($factory->createApiClient([]));
    }
}
