<?php

namespace Paysera\Component\RestClientCommon\Client;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
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
     * @return array|null
     */
    public function makeRequest(RequestInterface $request)
    {
        $response = $this->client->send($request);

        if ($response->getStatusCode() === StatusCodeInterface::STATUS_NO_CONTENT) {
            return null;
        }

        $body = $response->getBody()->getContents();
        if (empty($body)) {
            return null;
        }

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
        return $this->createRequestWithParameters($method, $uri, $entity !== null ? $entity->getData() : null);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array|null $parameters
     * @return RequestInterface
     */
    public function createRequestWithParameters($method, $uri, array $parameters = null)
    {
        $request = new Request($method, $uri);

        if ($parameters !== null) {
            if ($method === RequestMethodInterface::METHOD_GET) {
                $uri = $request->getUri()->withQuery(\GuzzleHttp\Psr7\build_query($parameters));
                $request = $request->withUri($uri);
            } else {
                $data = \GuzzleHttp\json_encode($parameters);
                $request = $request->withBody(\GuzzleHttp\Psr7\stream_for($data));
            }
        }

        return $request;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param resource|null $content
     * @return RequestInterface
     */
    public function createRequestWithContent($method, $uri, $content)
    {
        $request = new Request($method, $uri);

        if ($content !== null) {
            $request = $request->withBody(\GuzzleHttp\Psr7\stream_for($content));
        }

        return $request;
    }
}
