<?php

namespace Paysera\Component\RestClientCommon\Tests;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Paysera\Component\RestClientCommon\Exception\ClientException;
use Paysera\Component\RestClientCommon\Middleware\Authentication\BasicAuthentication;
use Paysera\Component\RestClientCommon\Middleware\Authentication\OAuthAuthentication;
use Paysera\Component\RestClientCommon\Tests\Client\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class AuthenticationMiddlewareTest extends TestCase
{
    public function testOAuthAuthenticationWithMacAdded()
    {
        $config = [
            OAuthAuthentication::TYPE => [
                'token' => [
                    'access_token' => 'abc',
                    'refresh_token' => 'xyz',
                    'mac_key' => '000',
                ]
            ]
        ];

        TestClientFactory::setHandler(
            new MockHandler([
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    ['Content-Type' => 'application/json'],
                    \GuzzleHttp\json_encode(['a' => 'b'])
                ),
                new Response(StatusCodeInterface::STATUS_UNAUTHORIZED),
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    ['Content-Type' => 'application/json'],
                    \GuzzleHttp\json_encode([
                        'access_token' => 'aaaa',
                        'mac_key' => 'bbb',
                        'expires_in' => 3600,
                        'mac_algorithm' => 'hmac_sha_256',
                        'token_type' => 'mac',
                    ])
                ),
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    ['Content-Type' => 'application/json'],
                    \GuzzleHttp\json_encode(['x' => 'y'])
                ),
            ])
        );

        $factory = new TestClientFactory($config);
        $client = $factory->getTestClient();

        // first time access_token is OK
        $client->getSomething();
        // second time it is expired
        $client->getSomething();

        $history = $factory::getHistory();

        $this->assertCount(4, $history);

        foreach ($factory::getHistory() as $key => $transaction) {
            /** @var RequestInterface $request */
            $request = $transaction['request'];
            $auth = $request->getHeaderLine('Authorization');
            if ($key === 2) {
                // on token refresh there should be no MAC token added
                $this->assertFalse(strpos($auth, 'MAC') === 0);

            } else {
                $this->assertTrue(strpos($auth, 'MAC') === 0);
            }
        }
    }

    public function testBasicAuthenticationAdded()
    {
        $username = 'username';
        $password = 'password';

        $config = [
            BasicAuthentication::TYPE => [
                'username' => $username,
                'password' => $password,
            ]
        ];

        TestClientFactory::setHandler(
            new MockHandler([
                new Response(StatusCodeInterface::STATUS_NO_CONTENT),
            ])
        );
        $factory = new TestClientFactory($config);
        $factory->getTestClient()->getSomething();

        $history = $factory::getHistory();
        $transaction = $history[0];

        $this->assertCount(1, $history);

        /** @var RequestInterface $request */
        $request = $transaction['request'];
        $auth = $request->getHeaderLine('Authorization');

        $this->assertSame(RequestMethodInterface::METHOD_GET, $request->getMethod());
        $this->assertSame('Basic ' . base64_encode(sprintf('%s:%s', $username, $password)), $auth);
    }

    public function testUnauthorizedResponse()
    {
        TestClientFactory::setHandler(
            new MockHandler([
                new Response(StatusCodeInterface::STATUS_UNAUTHORIZED),
            ])
        );

        $factory = new TestClientFactory([]);
        $client = $factory->getTestClient();

        try {
            $client->getSomething();
        } catch (ClientException $exception) {
            $this->assertNull($exception->getError());

            $this->assertEquals(
                "",
                $exception->getResponse()->getBody()->getContents()
            );

            $this->assertEquals(401, $exception->getResponse()->getStatusCode());
        }
    }
}
