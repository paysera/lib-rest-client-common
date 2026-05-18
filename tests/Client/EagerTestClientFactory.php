<?php

declare(strict_types=1);

namespace Paysera\Component\RestClientCommon\Tests\Client;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Paysera\Component\RestClientCommon\Client\ApiClient;
use Paysera\Component\RestClientCommon\Util\ClientFactoryAbstract;

class EagerTestClientFactory extends ClientFactoryAbstract
{
    private MockHandler $mockHandler;
    private ApiClient $apiClient;

    public function __construct(MockHandler $mockHandler)
    {
        $this->mockHandler = $mockHandler;
        $this->apiClient = $this->createApiClient([]);
    }

    public function getTestClient(): TestClient
    {
        return new TestClient($this->apiClient);
    }

    protected function getHandlerStack(): HandlerStack
    {
        return HandlerStack::create($this->mockHandler);
    }
}
