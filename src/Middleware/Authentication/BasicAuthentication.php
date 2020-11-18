<?php

namespace Paysera\Component\RestClientCommon\Middleware\Authentication;

use Paysera\Component\RestClientCommon\Exception\AuthenticationConfigurationException;
use Paysera\Component\RestClientCommon\Util\ConfigHandler;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 */
class BasicAuthentication implements AuthenticationMiddlewareInterface
{
    const TYPE = 'basic';

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
        if (!isset($auth['username']) || !isset($auth['password'])) {
            throw new AuthenticationConfigurationException(
                'Missing Authentication configuration. Username and Password is required'
            );
        }

        $credentials = base64_encode(sprintf(
            '%s:%s',
            $auth['username'],
            $auth['password']
        ));

        return sprintf('Basic %s', $credentials);
    }
}
