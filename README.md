## lib-rest-client-common ![](https://travis-ci.org/paysera/lib-rest-client-common.svg?branch=master)

Generic library for RESTful API clients

#### Usage

You should create `ClientFactory` class, which extends `ClientFactoryAbstract`.
In `ClientFactory` you can override any parent configuration if needed.

Simple example of `ClientFactory`:
```php
class TestClientFactory extends ClientFactoryAbstract
{
    const DEFAULT_BASE_URL = 'http://example.com/test/rest/v1/{locale}/';
    
    private $apiClient;

    public function __construct(array $options)
    {
        $this->apiClient = $this->createApiClient($options);
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
    
    public function withOptions(array $options)
    {
        return new TestClient($this->apiClient->withOptions($options));
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

$clientFactory = new ClientFactory([
    'base_url' => 'custom base url',
    'auth_base_url' => 'custom auth base url',
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
    'mac' => [
        'mac_id' => 'mac id',
        'mac_secret' => 'mac secret',
        'parameters' => [
            // list of needed parameters
        ]
    ],
    'url_parameters' => [
        'locale' => 'en',
        // list of base_url placeholder parameter values
    ],
    'headers' => [
        'Accept-Language' => 'en',
    ],
    // other configuration options
]);

$testClient = $clientFactory->getTestClient();
$data = $testClient->getSomething();
```
* Please note that only single authentication mechanism is supported.

In case you want to change some configuration options at runtime, use `TestClient::withOptions()`:

```php
$factory = new TestClientFactory([
    MacAuthentication::TYPE => [
        'mac_id' => $macId,
        'mac_secret' => $macSecret,
        'parameters' => [
            'user_id' => 100,
        ]
    ]
]);

$client = $factory->getTestClient();

$client2 = $factory->getTestClient()->withOptions([
    MacAuthentication::TYPE => [
        'parameters' => ['user_id' => 999],
    ]
]);
```

Here for `$client2` only `user_id` in `parameters` will be changed. Other configuration, like `mac_id`, `mac_secret` will
be left intact. 
