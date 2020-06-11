<?php

namespace Paysera\Component\RestClientCommon\Middleware\Authentication;

use Paysera\Component\RestClientCommon\Exception\AuthenticationConfigurationException;
use Paysera\Component\RestClientCommon\Util\ConfigHandler;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 */
class BearerAuthentication implements AuthenticationMiddlewareInterface
{
    const TYPE = 'bearer';

    public function __invoke(callable $nextHandler, RequestInterface $request, array $options)
    {
        $auth = ConfigHandler::getAuthentication($options, self::TYPE);

        if ($auth === null) {
            return $nextHandler($request, $options);
        }

        $nextRequest = $request->withHeader('Authorization', $this->buildAuthHeader($auth));

        return $nextHandler($nextRequest, $options);
    }

    private function buildAuthHeader(array $auth)
    {
        if (!isset($auth['token'])) {
            throw new AuthenticationConfigurationException(
                'Missing Authentication configuration. Token is required'
            );
        }

        return sprintf(
            'Bearer %s',
            $auth['token']
        );
    }
}
