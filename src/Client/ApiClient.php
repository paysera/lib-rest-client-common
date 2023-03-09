<?php

namespace Paysera\Component\RestClientCommon\Client;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Paysera\Component\RestClientCommon\Decoder\ResponseBodyDecoder;
use Paysera\Component\RestClientCommon\Entity\Entity;
use Paysera\Component\RestClientCommon\Util\ClientFactoryAbstract;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Psr\Http\Message\StreamInterface;

use function GuzzleHttp\Psr7\stream_for;

/**
 * @api
 */
class ApiClient
{
    private $client;
    private $responseBodyDecoder;
    private $clientFactory;
    private $options;

    public function __construct(
        ClientInterface $client,
        ResponseBodyDecoder $responseBodyDecoder,
        ClientFactoryAbstract $clientFactory,
        array $options
    ) {
        $this->client = $client;
        $this->responseBodyDecoder = $responseBodyDecoder;
        $this->clientFactory = $clientFactory;
        $this->options = $options;
    }

    public function withOptions(array $options)
    {
        $combinedOptions = array_replace_recursive($this->options, $options);
        return $this->clientFactory->createApiClient($combinedOptions);
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return array
     */
    public function makeRequest(RequestInterface $request, array $options = [])
    {
        $response = $this->makeRawRequest($request, $options);

        if ($response->getStatusCode() === StatusCodeInterface::STATUS_NO_CONTENT) {
            return null;
        }

        return $this->responseBodyDecoder->decodeContent($response->getHeaderLine('Content-Type'), $response);
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return ResponseInterface
     */
    public function makeRawRequest(RequestInterface $request, array $options = [])
    {
        return $this->client->send($request, $options);
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
                $uri = $request->getUri()->withQuery(http_build_query($parameters));
                $request = $request->withUri($uri);
            } else {
                $data = \GuzzleHttp\json_encode($parameters);
                $request = $this
                    ->createRequestWithContent($method, $uri, $data)
                    ->withHeader('Content-Type', 'application/json');
            }
        }

        return $request;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param resource|string|null $content
     * @return RequestInterface
     */
    public function createRequestWithContent($method, $uri, $content)
    {
        $request = new Request($method, $uri);

        if ($content !== null) {
            $request = $request->withBody($this->streamFor($content));
        }

        return $request;
    }

    /**
     * @param resource|string|null $content
     *
     * @return StreamInterface
     */
    private function streamFor($content)
    {
        if (method_exists(Utils::class, 'streamFor')) {
            return Utils::streamFor($content);
        }

        return stream_for($content);
    }
}
