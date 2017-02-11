<?php

namespace Paysera\Component\RestClientCommon\Middleware\Exception;

use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Paysera\Component\RestClientCommon\Middleware\Authentication\OAuthAuthentication;
use Paysera\Component\RestClientCommon\Util\ConfigHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RequestException
{
    public function __invoke(callable $nextHandler, RequestInterface $request, array $options)
    {
        return $nextHandler($request, $options)->then(
            function (ResponseInterface $response) use ($request, $nextHandler, $options) {
                $code = $response->getStatusCode();

                if ($code >= StatusCodeInterface::STATUS_BAD_REQUEST) {
                    if (
                        $code === StatusCodeInterface::STATUS_UNAUTHORIZED
                        && ConfigHandler::getAuthentication($options, OAuthAuthentication::TYPE) !== null
                    ) {
                        return $response;
                    }
                    throw GuzzleRequestException::create($request, $response);
                }
                return $response;
            }
        );
    }

    public function getMiddlewareFunction()
    {
        return function (callable $handler) {
            return function ($request, $options) use ($handler) {
                return $this($handler, $request, $options);
            };
        };
    }
}
