<?php

namespace Paysera\Component\RestClientCommon\Tests;

use Paysera\Component\RestClientCommon\Exception\ConfigurationException;
use Paysera\Component\RestClientCommon\Middleware\Authentication\MacAuthentication;
use Paysera\Component\RestClientCommon\Tests\Client\TestClient;
use Paysera\Component\RestClientCommon\Tests\Client\TestClientFactoryWithBaseUrlParams;
use Paysera\Component\RestClientCommon\Util\ConfigHandler;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use Fig\Http\Message\StatusCodeInterface;
use Paysera\Component\RestClientCommon\Tests\Client\TestClientFactory;
use Psr\Http\Message\RequestInterface;

class ClientWithOptionsTest extends TestCase
{
    public function testExceptionThrownWhenMissingUrlPlaceholders()
    {
        TestClientFactoryWithBaseUrlParams::setHandler(
            new MockHandler([
                new Response(StatusCodeInterface::STATUS_NO_CONTENT),
            ])
        );

        $this->expectException(ConfigurationException::class);
        new TestClientFactoryWithBaseUrlParams([]);
    }

    public function testUrlParametersAreReplacedOnWithOptions()
    {
        TestClientFactoryWithBaseUrlParams::setHandler(
            new MockHandler([
                new Response(StatusCodeInterface::STATUS_NO_CONTENT),
                new Response(StatusCodeInterface::STATUS_NO_CONTENT),
                new Response(StatusCodeInterface::STATUS_NO_CONTENT),
            ])
        );

        $factory = new TestClientFactoryWithBaseUrlParams([
            'url_parameters' => [
                'locale' => 'en',
                'shard_id' => 'adasd',
            ]
        ]);

        $client1 = $factory->getTestClient();
        $client1->getSomething();

        /** @var RequestInterface $request */
        $request = TestClientFactoryWithBaseUrlParams::getHistory()[0]['request'];
        $this->assertEquals('http://adasd.example.com/test/rest/v1/en/something', (string)$request->getUri());

        $client2 = $client1->withOptions(['url_parameters' => ['locale' => 'de']]);
        $client2->getSomething();

        /** @var RequestInterface $request */
        $request = TestClientFactoryWithBaseUrlParams::getHistory()[1]['request'];
        $this->assertEquals('http://adasd.example.com/test/rest/v1/de/something', (string)$request->getUri());

        $client1->getSomething();
        /** @var RequestInterface $request */
        $request = TestClientFactoryWithBaseUrlParams::getHistory()[2]['request'];
        $this->assertEquals('http://adasd.example.com/test/rest/v1/en/something', (string)$request->getUri());
    }

    public function testMacAddsParametersToExt()
    {
        TestClientFactory::setHandler(
            new MockHandler([
                new Response(StatusCodeInterface::STATUS_NO_CONTENT),
            ])
        );

        $factory = new TestClientFactory([
            MacAuthentication::TYPE => [
                'mac_id' => 'foo',
                'mac_secret' => 'bar',
                'parameters' => [
                    'user_id' => 100,
                    'client_id' => 111,
                ]
            ]
        ]);
        $client = $factory->getTestClient();

        $client->getSomething();

        /** @var RequestInterface $request */
        $request = TestClientFactory::getHistory()[0]['request'];

        $parameters = $this->getMacParameters($request);
        $this->assertCount(2, $parameters);
        $this->assertEquals(['user_id' => '100', 'client_id' => '111'], $parameters);
    }

    public function testWithOptionsOverridesExtParameters()
    {
        TestClientFactory::setHandler(
            new MockHandler([
                new Response(StatusCodeInterface::STATUS_NO_CONTENT),
                new Response(StatusCodeInterface::STATUS_NO_CONTENT),
                new Response(StatusCodeInterface::STATUS_NO_CONTENT),
                new Response(StatusCodeInterface::STATUS_NO_CONTENT),
                new Response(StatusCodeInterface::STATUS_NO_CONTENT),
            ])
        );

        $macId = 'foo';
        $macSecret = 'bar';

        $factory = new TestClientFactory([
            MacAuthentication::TYPE => [
                'mac_id' => $macId,
                'mac_secret' => $macSecret,
                'parameters' => [
                    'user_id' => 100,
                ]
            ]
        ]);
        $client = $factory->getTestClient();
        $client2 = $client->withOptions([
            MacAuthentication::TYPE => [
                'mac_id' => 'bbb',
            ]
        ]);
        $client3 = $factory->getTestClient()->withOptions([
            MacAuthentication::TYPE => [
                'mac_id' => 'xyz',
            ]
        ]);

        $this->checkClientHasMacParameters($client, $macId, $macSecret, 0);
        $this->checkModifiedClientHasModifiedMacParameters($client, $macId, $macSecret);
        $this->checkClientHoldsUnchangedReferences($client, $macId, $macSecret);
        $this->checkClientHasMacParameters($client2, 'bbb', $macSecret, 3);
        $this->checkClientHasMacParameters($client3, 'xyz', $macSecret, 4);
    }

    public function testAdditionalConfigurationAppendedInWithOptions()
    {
        TestClientFactory::setHandler(
            new MockHandler([
                new Response(StatusCodeInterface::STATUS_NO_CONTENT),
                new Response(StatusCodeInterface::STATUS_NO_CONTENT),
            ])
        );

        $factory = new TestClientFactory([]);

        $client1 = $factory->getTestClient();
        $client1->getSomething();

        $history = TestClientFactory::getHistory();

        /** @var RequestInterface $request */
        $request = $history[0]['request'];

        $this->assertCount(0, $request->getHeader('Accept-Language'));

        $client2 = $client1->withOptions(['headers' => ['Accept-Language' => 'lt']]);
        $client2->getSomething();

        $history = TestClientFactory::getHistory();

        /** @var RequestInterface $request */
        $request = $history[1]['request'];

        $this->assertCount(1, $request->getHeader('Accept-Language'));
        $this->assertEquals('lt', $request->getHeader('Accept-Language')[0]);
    }

    private function checkClientHasMacParameters(TestClient $client, $macId, $macSecret, $historyIndex)
    {
        $client->getSomething();

        $transaction = TestClientFactory::getHistory()[$historyIndex];
        /** @var RequestInterface $request */
        $request = $transaction['request'];

        $parameters = $this->getMacParameters($request);
        $this->assertCount(1, $parameters);
        $this->assertEquals(['user_id' => '100'], $parameters);

        $macConfig = ConfigHandler::getAuthentication($transaction['options'], MacAuthentication::TYPE);
        $this->assertArrayHasKey('mac_id', $macConfig);
        $this->assertArrayHasKey('mac_secret', $macConfig);
        $this->assertSame($macId, $macConfig['mac_id']);
        $this->assertSame($macSecret, $macConfig['mac_secret']);
    }

    private function checkModifiedClientHasModifiedMacParameters(TestClient $client, $macId, $macSecret)
    {
        $modifiedClient = $client->withOptions([
            MacAuthentication::TYPE => [
                'parameters' => [
                    'user_id' => 101,
                ]
            ]
        ]);
        $modifiedClient->getSomething();

        $transaction = TestClientFactory::getHistory()[1];
        /** @var RequestInterface $request */
        $request = $transaction['request'];
        $parameters = $this->getMacParameters($request);
        $this->assertCount(1, $parameters);
        $this->assertEquals(['user_id' => '101'], $parameters);

        $macConfig = ConfigHandler::getAuthentication($transaction['options'], MacAuthentication::TYPE);
        $this->assertArrayHasKey('mac_id', $macConfig);
        $this->assertArrayHasKey('mac_secret', $macConfig);
        $this->assertSame($macId, $macConfig['mac_id']);
        $this->assertSame($macSecret, $macConfig['mac_secret']);
    }

    private function checkClientHoldsUnchangedReferences(TestClient $client, $macId, $macSecret)
    {
        $client->getSomething();
        $transaction = TestClientFactory::getHistory()[2];
        /** @var RequestInterface $request */
        $request = $transaction['request'];
        $parameters = $this->getMacParameters($request);
        $this->assertCount(1, $parameters);
        $this->assertEquals(['user_id' => '100'], $parameters);

        $macConfig = ConfigHandler::getAuthentication($transaction['options'], MacAuthentication::TYPE);
        $this->assertArrayHasKey('mac_id', $macConfig);
        $this->assertArrayHasKey('mac_secret', $macConfig);
        $this->assertSame($macId, $macConfig['mac_id']);
        $this->assertSame($macSecret, $macConfig['mac_secret']);
    }

    private function getMacParameters(RequestInterface $request)
    {
        $macHeader = $request->getHeader('Authorization')[0];
        $macParts = explode(', ', $macHeader);
        foreach ($macParts as $macPart) {
            if (strpos($macPart, 'ext=') === 0) {
                parse_str(trim(substr($macPart, strlen('ext=')), '"'), $params);
                return $params;
            }
        }
        return [];
    }
}
