<?php

namespace Paysera\Component\RestClientCommon\Tests\Client;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Paysera\Component\RestClientCommon\Client\ApiClient;
use Paysera\Component\RestClientCommon\Util\ClientFactoryAbstract;

class TestClientFactory extends ClientFactoryAbstract
{
    const DEFAULT_BASE_URL = 'http://example.com/test/rest/v1/';

    private $apiClient;

    private static $history = [];
    private static $handler;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function getTestClient()
    {
        return new TestClient($this->apiClient);
    }

    public static function getHistory()
    {
        return static::$history;
    }

    public static function setHandler($handler)
    {
        static::$history = [];
        static::$handler = $handler;
    }

    protected static function getHandlerStack()
    {
        $stack = HandlerStack::create(static::$handler);
        $stack->push(Middleware::history(static::$history));

        return $stack;
    }
}
