<?php

namespace Paysera\Component\RestClientCommon\Middleware\Authentication;

use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Paysera\Component\RestClientCommon\Util\ConfigHandler;
use Paysera\Component\RestClientCommon\Exception\AuthenticationConfigurationException;

/**
 * @internal
 */
class ClientCertificateAuthentication implements AuthenticationMiddlewareInterface
{
    const TYPE = 'client_certificate';

    public function __invoke(callable $nextHandler, RequestInterface $request, array $options)
    {
        $auth = ConfigHandler::getAuthentication($options, self::TYPE);

        if ($auth === null) {
            return $nextHandler($request, $options);
        }

        if (!isset($auth['certificate_path']) || !isset($auth['private_key_path'])) {
            throw new AuthenticationConfigurationException('Certificate or private key is missing');
        }

        if (!file_exists($auth['certificate_path']) || !file_exists($auth['private_key_path'])) {
            throw new AuthenticationConfigurationException('Certificate or private key path is invalid');
        }

        $options[RequestOptions::SSL_KEY] = $auth['private_key_path'];

        if (isset($auth['certificate_password'])) {
            $options[RequestOptions::CERT] = [$auth['certificate_path'], $auth['certificate_password']];
        } else {
            $options[RequestOptions::CERT] = $auth['certificate_path'];
        }

        return $nextHandler($request, $options);
    }
}
