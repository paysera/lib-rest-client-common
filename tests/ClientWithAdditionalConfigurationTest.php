<?php

namespace Paysera\Component\RestClientCommon\Tests;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Handler\MockHandler;
use Psr\Http\Message\RequestInterface;
use Fig\Http\Message\StatusCodeInterface;
use Paysera\Component\RestClientCommon\Tests\Client\TestClientFactory;

class ClientWithAdditionalConfigurationTest extends TestCase
{
    public function testAdditionalRequestParametersAreBeingAddedToTheRequest()
    {
        TestClientFactory::setHandler(
            new MockHandler([
                new Response(StatusCodeInterface::STATUS_NO_CONTENT),
            ])
        );

        $factory = new TestClientFactory([
            'headers' => [
                'Accept-Language' => 'lt',
            ],
            'cookies' => CookieJar::fromArray(['sc' => 's3cure'], TestClientFactory::DEFAULT_BASE_URL),
            'proxy' => 'tcp://127.0.0.1:8125',
        ]);

        $client = $factory->getTestClient();
        $client->getSomething();

        $history = TestClientFactory::getHistory();

        /** @var RequestInterface $request */
        $request = $history[0]['request'];

        /** @var CookieJar $cookies */
        $cookies = $history[0]['options']['cookies'];
        $proxy = $history[0]['options']['proxy'];

        $this->assertEquals('lt', $request->getHeader('Accept-Language')[0]);
        $this->assertEquals('s3cure', $cookies->getCookieByName('sc')->getValue());
        $this->assertEquals('tcp://127.0.0.1:8125', $proxy);
    }
}
