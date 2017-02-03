<?php

namespace Paysera\Component\RestClientCommon\Authentication\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Paysera\Component\RestClientCommon\Authentication\Exception\AuthenticationConfigurationException;
use Paysera\Component\RestClientCommon\Client\ApiClient;
use Paysera\Component\RestClientCommon\Util\ConfigHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class OAuthAuthentication implements AuthenticationMiddlewareInterface
{
    const TYPE = 'oauth';

    private $apiClient;

    public function __construct(
        ApiClient $oauthClient
    ) {
        $this->apiClient = $oauthClient;
    }

    public function __invoke(callable $nextHandler, RequestInterface $request, array $options)
    {
        $auth = ConfigHandler::getAuthentication($options, self::TYPE);

        if (
            $auth === null
            || !isset($auth['token'])
            || !isset($auth['token']['access_token'])
        ) {
            throw new AuthenticationConfigurationException('AccessToken is missing');
        }

        return $nextHandler($request, $options)->then(
            function (ResponseInterface $response) use ($request, $options, $nextHandler) {
                return $this->checkOAuthFlow($response, $request, $options, $nextHandler);
            }
        );
    }

    public function getPriority()
    {
        return 10;
    }

    private function checkOAuthFlow(
        ResponseInterface $response,
        RequestInterface $request,
        array $options,
        callable $nextHandler
    ) {
        $auth = ConfigHandler::getAuthentication($options, self::TYPE);

        if ($response->getStatusCode() === StatusCodeInterface::STATUS_UNAUTHORIZED) {
            if (
                !empty($auth['token'])
                && isset($auth['token']['refresh_token'])
                && !isset($auth['token_refresh_attempted'])
            ) {
                return $this->repeatWithRefreshedAccessToken($options, $request, $nextHandler);
            }
        }

        return $response;
    }

    private function repeatWithRefreshedAccessToken(array $options, RequestInterface $request, callable $nextHandler)
    {
        $auth = ConfigHandler::getAuthentication($options, self::TYPE);
        $parameters = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $auth['token']['refresh_token'],
        ];

        $oauthRequest = $this->apiClient->createPlainRequest(RequestMethodInterface::METHOD_POST, 'token', $parameters);

        $auth = ConfigHandler::getAuthentication($options, self::TYPE);
        $token = $this->apiClient->makeRequest($oauthRequest);
        $auth['token_refresh_attempted'] = true;
        $auth['token'] = $token;

        $mac = [
            'token_type' => $token['token_type'],
            'mac_algorithm' => $token['mac_algorithm'],
            'expires_at' => time() + $token['expires_in'],
            'mac_id' => $token['access_token'],
            'mac_secret' => $token['mac_key'],
        ];

        ConfigHandler::setAuthentication($options, [
            self::TYPE => $auth,
            MacAuthentication::TYPE => $mac,
        ]);

        return $this($nextHandler, $request, $options);
    }
}
