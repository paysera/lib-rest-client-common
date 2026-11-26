<?php

namespace Paysera\Component\RestClientCommon\Tests;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\InvalidArgumentException;
use Paysera\Component\RestClientCommon\Client\ApiClient;
use Paysera\Component\RestClientCommon\Decoder\ResponseBodyDecoder;
use Paysera\Component\RestClientCommon\Util\ClientFactoryAbstract;
use PHPUnit\Framework\TestCase;

class ApiClientTest extends TestCase
{
    private function createApiClient(): ApiClient
    {
        return new ApiClient(
            $this->createMock(ClientInterface::class),
            new ResponseBodyDecoder(),
            $this->createMock(ClientFactoryAbstract::class),
            []
        );
    }

    public function testCreateRequestWithParametersEncodesJsonBody()
    {
        $request = $this->createApiClient()->createRequestWithParameters('POST', 'http://example.com', ['a' => 'b']);

        $this->assertSame('{"a":"b"}', (string)$request->getBody());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    public function testCreateRequestWithParametersThrowsGuzzleExceptionOnUnencodableData()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('json_encode error: Malformed UTF-8 characters, possibly incorrectly encoded');

        $this->createApiClient()->createRequestWithParameters('POST', 'http://example.com', ['a' => "\xB1\x31"]);
    }

    public function testCreateRequestWithContentWrapsStringInStream()
    {
        $request = $this->createApiClient()->createRequestWithContent('PUT', 'http://example.com', 'raw body');

        $this->assertSame('raw body', (string)$request->getBody());
    }
}
