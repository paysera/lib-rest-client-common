<?php

namespace Paysera\Component\RestClientCommon\Tests\Client;

use Fig\Http\Message\RequestMethodInterface;
use Paysera\Component\RestClientCommon\Client\ApiClient;

class TestClient
{
    private $apiClient;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function withOptions(array $options)
    {
        return new TestClient($this->apiClient->withOptions($options));
    }

    /**
     * @return null
     */
    public function getSomething()
    {
        $request = $this->apiClient->createRequest(
            RequestMethodInterface::METHOD_GET,
            sprintf('something'),
            null
        );
        return $this->apiClient->makeRequest($request);
    }
}
