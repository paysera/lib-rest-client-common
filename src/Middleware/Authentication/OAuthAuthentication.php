<?php

namespace Paysera\Component\RestClientCommon\Middleware\Authentication;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Paysera\Component\RestClientCommon\Entity\Entity;
use Paysera\Component\RestClientCommon\Exception\AuthenticationConfigurationException;
use Paysera\Component\RestClientCommon\Client\ApiClient;
use Paysera\Component\RestClientCommon\Exception\ClientException;
use Paysera\Component\RestClientCommon\Util\ConfigHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
class OAuthAuthentication implements AuthenticationMiddlewareInterface
{
    const TYPE = 'oauth';
    const REFRESH_ATTEMPTED = 'token_refresh_attempted';
    const TOKEN_REFRESH_REQUEST = 'token_refresh_request';

    private $apiClient;

    public function __construct(
        ApiClient $oauthClient
    ) {
        $this->apiClient = $oauthClient;
    }

    public function __invoke(callable $nextHandler, RequestInterface $request, array $options)
    {
        $auth = ConfigHandler::getAuthentication($options, self::TYPE);

        if ($auth === null) {
            return $nextHandler($request, $options);
        }

        if (!isset($auth['token']) || !isset($auth['token']['access_token'])) {
            throw new AuthenticationConfigurationException('AccessToken is missing');
        }

        if (!isset($options[self::TOKEN_REFRESH_REQUEST])) {
            ConfigHandler::setAuthentication($options, [
                self::TYPE => $auth,
                MacAuthentication::TYPE => $this->getMacToken($auth['token']),
            ]);
        }

        return $nextHandler($request, $options)->then(
            function (ResponseInterface $response) use ($request, $options, $nextHandler) {
                return $this->checkOAuthFlow($response, $request, $options, $nextHandler);
            }
        );
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
                && !isset($auth[self::REFRESH_ATTEMPTED])
            ) {
                return $this->repeatWithRefreshedAccessToken($options, $request, $nextHandler);
            } else {
                throw ClientException::create($request, $response);
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

        $oauthRequest = $this->apiClient
            ->createRequest(RequestMethodInterface::METHOD_POST, 'token', new Entity($parameters));

        $token = $this->apiClient->makeRequest($oauthRequest, [self::TOKEN_REFRESH_REQUEST => true]);
        $auth[self::REFRESH_ATTEMPTED] = true;
        $auth['token'] = $token;

        ConfigHandler::setAuthentication($options, [
            self::TYPE => $auth,
            MacAuthentication::TYPE => $this->getMacToken($token),
        ]);

        return $this($nextHandler, $request, $options);
    }

    private function getMacToken(array $token)
    {
        return [
            'token_type' => isset($token['token_type']) ? $token['token_type'] : null,
            'mac_algorithm' => isset($token['mac_algorithm']) ? $token['mac_algorithm'] : null,
            'expires_at' => isset($token['expires_in']) ? time() + $token['expires_in'] : null,
            'mac_id' => isset($token['access_token']) ? $token['access_token'] : null,
            'mac_secret' => isset($token['mac_key']) ? $token['mac_key'] : null,
        ];
    }
}
