<?php

namespace Paysera\Component\RestClientCommon\Authentication\Middleware;

use Paysera\Component\RestClientCommon\Authentication\Exception\AuthenticationConfigurationException;
use Paysera\Component\RestClientCommon\Util\ConfigHandler;
use Psr\Http\Message\RequestInterface;

class BasicAuthentication implements AuthenticationMiddlewareInterface
{
    const TYPE = 'basic';

    public function __invoke(callable $nextHandler, RequestInterface $request, array $options)
    {
        $auth = ConfigHandler::getAuthentication($options, self::TYPE);

        if ($auth === null) {
            return $nextHandler($request, $auth);
        }

        $nextRequest = $request->withHeader('Authorization', $this->buildAuthHeader($auth));

        return $nextHandler($nextRequest, $auth);
    }

    public function getPriority()
    {
        return 100;
    }

    private function buildAuthHeader(array $auth)
    {
        if (!isset($auth['username']) || !isset($auth['password'])) {
            throw new AuthenticationConfigurationException(
                'Missing Authentication configuration. Username and Password is required'
            );
        }

        return base64_encode(sprintf(
            '%s:%s',
            $auth['username'],
            $auth['password']
        ));
    }
}
