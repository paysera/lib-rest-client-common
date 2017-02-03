<?php

namespace Paysera\Component\RestClientCommon\Authentication\Middleware;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;

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

    /**
     * @return int
     */
    public function getPriority();
}
