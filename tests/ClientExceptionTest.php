<?php

namespace Paysera\Component\RestClientCommon\Tests;

use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use Fig\Http\Message\StatusCodeInterface;
use Paysera\Component\RestClientCommon\Tests\Client\TestClientFactory;
use Paysera\Component\RestClientCommon\Middleware\Authentication\OAuthAuthentication;
use Paysera\Component\RestClientCommon\Exception\ClientException;

class ClientExceptionTest extends TestCase
{
    private $config;

    public function setUp(): void
    {
        $this->config = [
            OAuthAuthentication::TYPE => [
                'token' => [
                    'access_token' => 'abc',
                    'refresh_token' => 'xyz',
                    'mac_key' => '000',
                ]
            ]
        ];
    }

    public function testExceptionPropertiesMapping()
    {
        TestClientFactory::setHandler(
            new MockHandler([
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND,
                    ['Content-Type' => 'application/json'],
                    \GuzzleHttp\json_encode(['error' => 'NOT_FOUND', 'error_description' => 'Resource not found'])
                ),
            ])
        );

        $factory = new TestClientFactory($this->config);
        $client = $factory->getTestClient();

        try {
            $client->getSomething();
        } catch (ClientException $exception) {
            $this->assertEquals('NOT_FOUND', $exception->getError());
            $this->assertEquals('Resource not found', $exception->getErrorDescription());

            // Test if no fingerprints left after reading response stream
            $exceptionContents = json_decode($exception->getResponse()->getBody()->getContents(), true);
            $this->assertEquals($exceptionContents['error'], $exception->getError());
            $this->assertEquals($exceptionContents['error_description'], $exception->getErrorDescription());
        }
    }

    public function testExceptionResponseRewind_with_invalid_response()
    {
        TestClientFactory::setHandler(
            new MockHandler([
                new Response(
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    ['Content-Type' => 'application/json'],
                    "{'error': 'INVALID_RESPONSE_WITHOUT_DOUBLE_QUOTES'}"
                ),
            ])
        );

        $factory = new TestClientFactory($this->config);
        $client = $factory->getTestClient();

        try {
            $client->getSomething();
        } catch (ClientException $exception) {
            $this->assertNull($exception->getError());

            // Test if response stream was rewinded successfully
            $this->assertEquals(
                "{'error': 'INVALID_RESPONSE_WITHOUT_DOUBLE_QUOTES'}",
                $exception->getResponse()->getBody()->getContents()
            );
        }
    }

    public function testExceptionResponseWhenStreaming()
    {
        TestClientFactory::setHandler(
            new MockHandler([
                new Response(
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    ['Content-Type' => 'application/json'],
                    new NoSeekStream(Utils::streamFor(\GuzzleHttp\json_encode([
                        'error' => 'BAD_REQUEST',
                        'error_description' => 'Wrong parameters'
                    ])))
                ),
            ])
        );

        $factory = new TestClientFactory($this->config);
        $client = $factory->getTestClient();

        $this->expectException(ClientException::class);

        $client->getSomething();
    }
}
