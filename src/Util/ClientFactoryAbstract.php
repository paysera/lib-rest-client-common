<?php

namespace Paysera\Component\RestClientCommon\Util;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Paysera\Component\RestClientCommon\Authentication\AuthenticationProvider;
use Paysera\Component\RestClientCommon\Authentication\Middleware\BasicAuthentication;
use Paysera\Component\RestClientCommon\Authentication\Middleware\MacAuthentication;
use Paysera\Component\RestClientCommon\Authentication\Middleware\OAuthAuthentication;
use Paysera\Component\RestClientCommon\Client\ApiClient;

class ClientFactoryAbstract
{
    protected static $baseUrl;
    protected static $oauthBaseUrl;

    private static $config = [];
    private static $handlerStack;

    public static function create(array $options)
    {
        if (isset($options['base_url'])) {
            static::$baseUrl = $options['base_url'];
        }

        if (isset($options[BasicAuthentication::TYPE])) {
            ConfigHandler::setAuthentication(
                static::$config,
                [
                    BasicAuthentication::TYPE => $options[BasicAuthentication::TYPE],
                ]
            );
        }
        if (isset($options[OAuthAuthentication::TYPE])) {
            ConfigHandler::setAuthentication(
                static::$config,
                [
                    OAuthAuthentication::TYPE => $options[OAuthAuthentication::TYPE],
                ]
            );
        }

        return new static(static::buildApiClient(static::$baseUrl));
    }

    protected static function buildApiClient($baseUrl)
    {
        $client = new Client([
            'base_uri' => $baseUrl,
            'handler' => static::getHandlerStack(),
            ConfigHandler::CONFIG_NAMESPACE => static::$config,
        ]);

        return new ApiClient($client);
    }

    protected static function getHandlerStack()
    {
        if (static::$handlerStack === null) {
            $stack = HandlerStack::create();
            static::addSecurity($stack);

            static::$handlerStack = $stack;
        }

        return static::$handlerStack;
    }

    protected static function addSecurity(HandlerStack $stack)
    {
        $authProvider = new AuthenticationProvider();
        $authProvider->addMiddleware(new BasicAuthentication());
        $authProvider->addMiddleware(new MacAuthentication());
        $authProvider->addMiddleware(new OAuthAuthentication(static::buildApiClient(static::$oauthBaseUrl)));

        foreach ($authProvider->getMiddlewares() as $middleware) {
            $stack->push($middleware, AuthenticationProvider::HANDLER_POSITION);
        }
    }
}
