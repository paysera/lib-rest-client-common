<?php

namespace Paysera\Component\RestClientCommon\Tests;

use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Paysera\Component\RestClientCommon\Middleware\Authentication\MacAuthentication;
use PHPUnit\Framework\TestCase;

class MacAuthenticationTest extends TestCase
{
    public function testBodyHashIsGeneratedForNonMultipartContent()
    {
        $middleware = new MacAuthentication();
        $requestBody = '{"key":"value"}';
        $expectedBodyHash = base64_encode(hash('sha256', $requestBody, true));

        $request = new Request(
            'POST',
            'http://example.com/api/endpoint',
            ['Content-Type' => 'application/json'],
            $requestBody
        );

        $options = [
            'paysera' => [
                'authentication' => [
                    'mac' => [
                        'mac_id' => 'test_mac_id',
                        'mac_secret' => 'test_mac_secret',
                    ],
                ],
            ],
        ];

        $nextCalled = false;
        $capturedRequest = null;
        $nextHandler = function ($req, $opts) use (&$nextCalled, &$capturedRequest) {
            $nextCalled = true;
            $capturedRequest = $req;
            return new Response(StatusCodeInterface::STATUS_OK);
        };

        $middleware($nextHandler, $request, $options);

        $this->assertTrue($nextCalled);
        $this->assertNotNull($capturedRequest);

        $authHeader = $capturedRequest->getHeaderLine('Authorization');
        $this->assertStringStartsWith('MAC ', $authHeader);

        // Extract ext parameter from the Authorization header
        preg_match('/ext="([^"]+)"/', $authHeader, $matches);
        $this->assertNotEmpty($matches, 'ext parameter should be present in Authorization header');

        $extParams = [];
        parse_str($matches[1], $extParams);

        $this->assertArrayHasKey('body_hash', $extParams, 'body_hash should be present for non-multipart content');
        $this->assertSame($expectedBodyHash, $extParams['body_hash']);
    }

    public function testBodyHashIsSkippedForMultipartStream()
    {
        $middleware = new MacAuthentication();

        $multipartBody = new MultipartStream([
            [
                'name' => 'file',
                'contents' => 'file content',
                'filename' => 'test.txt',
            ],
            [
                'name' => 'field',
                'contents' => 'value',
            ],
        ]);

        $request = new Request(
            'POST',
            'http://example.com/api/upload',
            ['Content-Type' => 'multipart/form-data'],
            $multipartBody
        );

        $options = [
            'paysera' => [
                'authentication' => [
                    'mac' => [
                        'mac_id' => 'test_mac_id',
                        'mac_secret' => 'test_mac_secret',
                    ],
                ],
            ],
        ];

        $nextCalled = false;
        $capturedRequest = null;
        $nextHandler = function ($req, $opts) use (&$nextCalled, &$capturedRequest) {
            $nextCalled = true;
            $capturedRequest = $req;
            return new Response(StatusCodeInterface::STATUS_OK);
        };

        $middleware($nextHandler, $request, $options);

        $this->assertTrue($nextCalled);
        $this->assertNotNull($capturedRequest);

        $authHeader = $capturedRequest->getHeaderLine('Authorization');
        $this->assertStringStartsWith('MAC ', $authHeader);

        // Check if ext parameter exists
        preg_match('/ext="([^"]*)"/', $authHeader, $matches);

        if (!empty($matches)) {
            $extParams = [];
            parse_str($matches[1], $extParams);
            $this->assertArrayNotHasKey('body_hash', $extParams, 'body_hash should NOT be present for MultipartStream');
        } else {
            // If ext is not present at all, that's also valid (no body_hash means no ext in this case)
            $this->assertTrue(true);
        }
    }

    public function testBodyHashIsSkippedForEmptyContent()
    {
        $middleware = new MacAuthentication();

        $request = new Request(
            'GET',
            'http://example.com/api/endpoint',
            []
        );

        $options = [
            'paysera' => [
                'authentication' => [
                    'mac' => [
                        'mac_id' => 'test_mac_id',
                        'mac_secret' => 'test_mac_secret',
                    ],
                ],
            ],
        ];

        $nextCalled = false;
        $capturedRequest = null;
        $nextHandler = function ($req, $opts) use (&$nextCalled, &$capturedRequest) {
            $nextCalled = true;
            $capturedRequest = $req;
            return new Response(StatusCodeInterface::STATUS_OK);
        };

        $middleware($nextHandler, $request, $options);

        $this->assertTrue($nextCalled);
        $this->assertNotNull($capturedRequest);

        $authHeader = $capturedRequest->getHeaderLine('Authorization');
        $this->assertStringStartsWith('MAC ', $authHeader);

        // Check if ext parameter exists
        preg_match('/ext="([^"]*)"/', $authHeader, $matches);

        if (!empty($matches)) {
            $extParams = [];
            parse_str($matches[1], $extParams);
            $this->assertArrayNotHasKey('body_hash', $extParams, 'body_hash should NOT be present for empty content');
        } else {
            // If ext is not present at all, that's also valid (no body_hash means no ext in this case)
            $this->assertTrue(true);
        }
    }

    public function testBodyHashWithAdditionalParameters()
    {
        $middleware = new MacAuthentication();
        $requestBody = '{"key":"value"}';
        $expectedBodyHash = base64_encode(hash('sha256', $requestBody, true));

        $request = new Request(
            'POST',
            'http://example.com/api/endpoint',
            ['Content-Type' => 'application/json'],
            $requestBody
        );

        $options = [
            'paysera' => [
                'authentication' => [
                    'mac' => [
                        'mac_id' => 'test_mac_id',
                        'mac_secret' => 'test_mac_secret',
                        'parameters' => [
                            'custom_param' => 'custom_value',
                        ],
                    ],
                ],
            ],
        ];

        $nextCalled = false;
        $capturedRequest = null;
        $nextHandler = function ($req, $opts) use (&$nextCalled, &$capturedRequest) {
            $nextCalled = true;
            $capturedRequest = $req;
            return new Response(StatusCodeInterface::STATUS_OK);
        };

        $middleware($nextHandler, $request, $options);

        $this->assertTrue($nextCalled);
        $this->assertNotNull($capturedRequest);

        $authHeader = $capturedRequest->getHeaderLine('Authorization');
        $this->assertStringStartsWith('MAC ', $authHeader);

        preg_match('/ext="([^"]+)"/', $authHeader, $matches);
        $this->assertNotEmpty($matches, 'ext parameter should be present in Authorization header');

        $extParams = [];
        parse_str($matches[1], $extParams);

        $this->assertArrayHasKey('body_hash', $extParams, 'body_hash should be present');
        $this->assertArrayHasKey('custom_param', $extParams, 'custom parameter should be present');
        $this->assertSame($expectedBodyHash, $extParams['body_hash']);
        $this->assertSame('custom_value', $extParams['custom_param']);
    }
}
