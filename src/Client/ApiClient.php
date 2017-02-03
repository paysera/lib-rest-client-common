<?php

namespace Paysera\Component\RestClientCommon\Client;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Paysera\Component\RestClientCommon\Entity\Entity;
use Psr\Http\Message\RequestInterface;

class ApiClient
{
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @param RequestInterface $request
     * @return array
     */
    public function makeRequest(RequestInterface $request)
    {
        $response = $this->client->send($request);
        $body = $response->getBody()->getContents();

        return \GuzzleHttp\json_decode($body, true);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param Entity $entity
     *
     * @return RequestInterface
     */
    public function createRequest($method, $uri, Entity $entity = null)
    {
        return $this->createPlainRequest($method, $uri, $entity !== null ? $entity->getData() : null);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param mixed $data
     * @return RequestInterface
     */
    public function createPlainRequest($method, $uri, $data)
    {
        $request = new Request($method, $uri);

        if ($data !== null) {
            if ($method === RequestMethodInterface::METHOD_GET && is_array($data)) {
                $uri = $request->getUri()->withQuery(\GuzzleHttp\Psr7\build_query($data));
                $request = $request->withUri($uri);
            } else {
                if (is_array($data)) {
                    $data = \GuzzleHttp\json_encode($data);
                }
                $request = $request->withBody(\GuzzleHttp\Psr7\stream_for($data));
            }
        }

        return $request;
    }
}
