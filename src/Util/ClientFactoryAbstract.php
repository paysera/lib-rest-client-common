<?php

namespace Paysera\Component\RestClientCommon\Util;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Paysera\Component\RestClientCommon\Authentication\AuthenticationProvider;
use Paysera\Component\RestClientCommon\Decoder\ResponseBodyDecoder;
use Paysera\Component\RestClientCommon\Decoder\ResponseDecoder\JsonResponseDecoder;
use Paysera\Component\RestClientCommon\Exception\ConfigurationException;
use Paysera\Component\RestClientCommon\Middleware\Authentication\BasicAuthentication;
use Paysera\Component\RestClientCommon\Middleware\Authentication\BearerAuthentication;
use Paysera\Component\RestClientCommon\Middleware\Authentication\ClientCertificateAuthentication;
use Paysera\Component\RestClientCommon\Middleware\Authentication\MacAuthentication;
use Paysera\Component\RestClientCommon\Middleware\Authentication\OAuthAuthentication;
use Paysera\Component\RestClientCommon\Client\ApiClient;
use Paysera\Component\RestClientCommon\Middleware\Exception\RequestExceptionMiddleware;

/**
 * @api
 */
abstract class ClientFactoryAbstract
{
    const DEFAULT_BASE_URL = '';
    const AUTH_BASE_URL = 'https://wallet.paysera.com/oauth/v1/';

    private static $availableAuthTypes = [
        BasicAuthentication::TYPE,
        BearerAuthentication::TYPE,
        OAuthAuthentication::TYPE,
        MacAuthentication::TYPE,
        ClientCertificateAuthentication::TYPE,
    ];

    /**
     * @deprecated create by using 'new' keyword
     *
     * @param array $options
     * @return ClientFactoryAbstract
     */
    public static function create(array $options)
    {
        return new static($options);
    }

    public function createApiClient(array $options)
    {
        $config = [];
        $baseUrl = static::DEFAULT_BASE_URL;
        $authBaseUrl = static::AUTH_BASE_URL;

        if (isset($options['base_url'])) {
            $baseUrl = $options['base_url'];
        }
        if (isset($options['auth_base_url'])) {
            $authBaseUrl = $options['auth_base_url'];
        }

        $baseUrl = $this->parseBaseUrlParameters($baseUrl, $options);

        foreach (self::$availableAuthTypes as $type) {
            if (isset($options[$type])) {
                ConfigHandler::setAuthentication($config, [$type => $options[$type]]);
                break;
            }
        }

        $config = array_merge($config, $options);

        return $this->buildClient($baseUrl, $authBaseUrl, $config, $options);
    }

    protected function getHandlerStack()
    {
        return HandlerStack::create();
    }

    protected function addSecurity(HandlerStack $stack, ApiClient $oAuthClient)
    {
        $authProvider = new AuthenticationProvider();
        $authProvider->addMiddleware(new BasicAuthentication());
        $authProvider->addMiddleware(new BearerAuthentication());
        $authProvider->addMiddleware(new MacAuthentication());
        $authProvider->addMiddleware(new ClientCertificateAuthentication());
        $authProvider->addMiddleware(new OAuthAuthentication($oAuthClient), 200);

        foreach ($authProvider->getMiddlewares() as $middleware) {
            $stack->unshift($middleware);
        }
    }

    protected function getResponseBodyDecoder()
    {
        $decoder = new ResponseBodyDecoder();
        $decoder->addDecoder(new JsonResponseDecoder(), 'application/json');

        return $decoder;
    }

    /**
     * @param string $baseUrl
     * @param array $options
     *
     * @return string
     * @throws ConfigurationException
     */
    private function parseBaseUrlParameters($baseUrl, array $options)
    {
        preg_match_all('#{([\w|-]+)}#', $baseUrl, $matches);
        foreach ($matches[1] as $match) {
            if (!isset($options['url_parameters'][$match])) {
                throw new ConfigurationException(sprintf(
                    'Found placeholder {%s} in base_url, but no value provided in url_parameters option',
                    $match
                ));
            }
            $value = $options['url_parameters'][$match];
            $baseUrl = strtr($baseUrl, ['{' . $match . '}' => $value]);
        }

        return $baseUrl;
    }

    /**
     * @param string $baseUrl
     * @param string $authBaseUrl
     * @param array $config
     * @param array $options
     * @return ApiClient
     */
    private function buildClient($baseUrl, $authBaseUrl, array $config, array $options)
    {
        $stack = $this->getHandlerStack();
        $responseBodyDecoder = $this->getResponseBodyDecoder();

        $client = $this->buildApiClient($baseUrl, $stack, $config, $responseBodyDecoder, $options);
        $oAuthClient = $this->buildApiClient($authBaseUrl, $stack, $config, $responseBodyDecoder, $options);

        $this->addSecurity($stack, $oAuthClient);

        $stack->unshift((new RequestExceptionMiddleware())->getMiddlewareFunction());

        return $client;
    }

    /**
     * @param string $baseUrl
     * @param HandlerStack $stack
     * @param array $config
     * @param ResponseBodyDecoder $responseBodyDecoder
     * @param array $options
     * @return ApiClient
     */
    private function buildApiClient(
        $baseUrl,
        HandlerStack $stack,
        array $config,
        ResponseBodyDecoder $responseBodyDecoder,
        array $options
    ) {
        $config['base_uri'] = $baseUrl;
        $config['handler'] = $stack;
        $config['http_errors'] = false;

        $client = new Client($config);

        return new ApiClient($client, $responseBodyDecoder, $this, $options);
    }
}
