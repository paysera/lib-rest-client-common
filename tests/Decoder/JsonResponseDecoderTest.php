<?php

namespace Paysera\Component\RestClientCommon\Tests\Decoder;

use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Psr7\Response;
use Paysera\Component\RestClientCommon\Decoder\ResponseDecoder\JsonResponseDecoder;
use PHPUnit\Framework\TestCase;

class JsonResponseDecoderTest extends TestCase
{
    public function testDecodesJsonToArray()
    {
        $decoded = (new JsonResponseDecoder())->decode(new Response(200, [], '{"a":{"b":1}}'));

        $this->assertSame(['a' => ['b' => 1]], $decoded);
    }

    public function testThrowsGuzzleExceptionOnInvalidJson()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('json_decode error: Syntax error');

        (new JsonResponseDecoder())->decode(new Response(200, [], 'not json'));
    }
}
