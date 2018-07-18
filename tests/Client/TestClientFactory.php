<?php

namespace Paysera\Component\RestClientCommon\Tests\Client;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Paysera\Component\RestClientCommon\Util\ClientFactoryAbstract;

class TestClientFactory extends ClientFactoryAbstract
{
    const DEFAULT_BASE_URL = 'http://example.com/test/rest/v1/';

    private static $history = [];
    private static $handler;

    private $apiClient;

    public function __construct(array $options)
    {
        $this->apiClient = $this->createApiClient($options);
        self::$history = [];
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

    protected function getHandlerStack()
    {
        $stack = HandlerStack::create(static::$handler);
        $stack->push(Middleware::history(static::$history));

        return $stack;
    }
}
