<?php

namespace Paysera\Component\RestClientCommon\Tests\Exception;

use GuzzleHttp\Psr7\Response;
use Paysera\Component\RestClientCommon\Exception\RequestException;
use PHPUnit\Framework\Error\Deprecated;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class RequestExceptionTest extends TestCase
{

    public function test__construct()
    {
        $requestMock = $this->createMock(RequestInterface::class);
        $responseMock = new Response(200, [], 'some body');

        $errorReporting = error_reporting();

        error_reporting(-1);

        try {
            $exception = RequestException::create($requestMock, $responseMock);

            $this->assertNull($exception->getError());
            $this->assertSame('some body', $responseMock->getBody()->getContents());
        } catch (Deprecated $exception) {
            $this->assertEquals(
                'Exception::__construct(): Passing null to parameter #1 ($message) of type string is deprecated',
                $exception->getMessage()
            );
            $this->fail($exception->getMessage());
        } finally {
            error_reporting($errorReporting);
        }
    }
}
