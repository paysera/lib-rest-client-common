<?php

namespace Paysera\Component\RestClientCommon\Middleware\Authentication;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;

/**
 * @api
 */
interface AuthenticationMiddlewareInterface
{
    /**
     * @param callable $nextHandler
     * @param RequestInterface $request
     * @param array $options
     *
     * @return PromiseInterface
     */
    public function __invoke(callable $nextHandler, RequestInterface $request, array $options);
}
