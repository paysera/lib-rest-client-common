<?php

namespace Paysera\Component\RestClientCommon\Middleware\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Paysera\Component\RestClientCommon\Exception\ClientException;
use Paysera\Component\RestClientCommon\Exception\ServerException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
class RequestExceptionMiddleware
{
    public function __invoke(callable $nextHandler, RequestInterface $request, array $options)
    {
        return $nextHandler($request, $options)->then(
            function (ResponseInterface $response) use ($request, $nextHandler, $options) {
                $code = $response->getStatusCode();

                if ($code >= StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR) {
                    throw ServerException::create($request, $response);
                }
                if ($code >= StatusCodeInterface::STATUS_BAD_REQUEST) {
                    throw ClientException::create($request, $response);
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
