## lib-rest-client-common ![](https://travis-ci.org/paysera/lib-rest-client-common.svg?branch=master)

Generic library for RESTful API clients

#### Usage

You should create `ClientFactory` class, which extends `ClientFactoryAbstract`.
In `ClientFactory` you can override any parent configuration if needed.

Simple example of `ClientFactory`:
```php
class TestClientFactory extends ClientFactoryAbstract
{
    const DEFAULT_BASE_URL = 'http://example.com/test/rest/v1/';

    private $apiClient;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function getTestClient()
    {
        return new TestClient($this->apiClient);
    }
}
```

Using this pattern you can reuse same `ApiClient` with it's authentication and other options in different APIs.


In addition to `ClientFactory`, you should create also the `Client` itself. 
Finally you will use the `Client` itself, so it should contain all the methods your API provides.
 
Simple example of `TestClient`:
```php
class TestClient
{
    private $apiClient;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * @return array
     */
    public function getSomething()
    {
        $request = $this->apiClient->createRequest(
            RequestMethodInterface::METHOD_GET,
            sprintf('/something'),
            null
        );
        return $this->apiClient->makeRequest($request);
    }
}
```

You should implement mapping or data transformation where applicable in `TestClient` methods.

#### Example:

```php

use Paysera\Client\CategoryClient\ClientFactory;

$clientFactory = ClientFactory::create([
    'base_url' => 'custom base url',
    'basic' => [
        'username' => 'user',
        'password' => 'pass'
    ],
    'oauth' => [
        'token' => [
            'access_token' => 'your oauth access token',
            'refresh_token' => 'your oauth refresh token',
        ],
    ],
    // other configuration options
]);

$testClient = $clientFactory->getTestClient();

$data = $testClient->getSomething();

```
