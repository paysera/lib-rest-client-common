<?php

namespace Paysera\Component\RestClientCommon\Util;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Paysera\Component\RestClientCommon\Authentication\AuthenticationProvider;
use Paysera\Component\RestClientCommon\Decoder\ResponseBodyDecoder;
use Paysera\Component\RestClientCommon\Decoder\ResponseDecoder\JsonResponseDecoder;
use Paysera\Component\RestClientCommon\Middleware\Authentication\BasicAuthentication;
use Paysera\Component\RestClientCommon\Middleware\Authentication\MacAuthentication;
use Paysera\Component\RestClientCommon\Middleware\Authentication\OAuthAuthentication;
use Paysera\Component\RestClientCommon\Client\ApiClient;
use Paysera\Component\RestClientCommon\Middleware\Exception\RequestExceptionMiddleware;

class ClientFactoryAbstract
{
    const DEFAULT_BASE_URL = '';
    const OAUTH_BASE_URL = 'https://wallet.paysera.com/oauth/v1/';

    public static function create(array $options)
    {
        $config = [];
        $baseUrl = static::DEFAULT_BASE_URL;

        if (isset($options['base_url'])) {
            $baseUrl = $options['base_url'];
        }

        if (isset($options[BasicAuthentication::TYPE])) {
            ConfigHandler::setAuthentication(
                $config,
                [
                    BasicAuthentication::TYPE => $options[BasicAuthentication::TYPE],
                ]
            );
        }
        if (isset($options[OAuthAuthentication::TYPE])) {
            ConfigHandler::setAuthentication(
                $config,
                [
                    OAuthAuthentication::TYPE => $options[OAuthAuthentication::TYPE],
                ]
            );
        }

        return new static(static::buildClient($baseUrl, $config));
    }

    /**
     * @param string $baseUrl
     * @param array $config
     * @return ApiClient
     */
    protected static function buildClient($baseUrl, array $config)
    {
        $stack = static::getHandlerStack();
        $responseBodyDecoder = static::getResponseBodyDecoder();

        $client = static::buildApiClient($baseUrl, $stack, $config, $responseBodyDecoder);
        $oAuthClient = static::buildApiClient(static::OAUTH_BASE_URL, $stack, $config, $responseBodyDecoder);

        static::addSecurity($stack, $oAuthClient);

        $stack->unshift((new RequestExceptionMiddleware())->getMiddlewareFunction());

        return $client;
    }

    protected static function getHandlerStack()
    {
        return HandlerStack::create();
    }

    protected static function addSecurity(HandlerStack $stack, ApiClient $oAuthClient)
    {
        $authProvider = new AuthenticationProvider();
        $authProvider->addMiddleware(new BasicAuthentication());
        $authProvider->addMiddleware(new MacAuthentication());
        $authProvider->addMiddleware(new OAuthAuthentication($oAuthClient), 200);

        foreach ($authProvider->getMiddlewares() as $middleware) {
            $stack->unshift($middleware);
        }
    }

    protected static function getResponseBodyDecoder()
    {
        $decoder = new ResponseBodyDecoder();

        $decoder->addDecoder(new JsonResponseDecoder(), 'application/json');

        return $decoder;
    }

    /**
     * @param string $baseUrl
     * @param HandlerStack $stack
     * @param array $config
     * @param ResponseBodyDecoder $responseBodyDecoder
     * @return ApiClient
     */
    private static function buildApiClient(
        $baseUrl,
        HandlerStack $stack,
        array $config,
        ResponseBodyDecoder $responseBodyDecoder
    ) {
        $config['base_uri'] = $baseUrl;
        $config['handler'] = $stack;
        $config['http_errors'] = false;

        $client = new Client($config);

        return new ApiClient($client, $responseBodyDecoder);
    }
}
