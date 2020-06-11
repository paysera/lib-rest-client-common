<?php

namespace Paysera\Component\RestClientCommon\Authentication;

use Paysera\Component\RestClientCommon\Exception\AuthenticationConfigurationException;
use Paysera\Component\RestClientCommon\Middleware\Authentication\AuthenticationMiddlewareInterface;

/**
 * @internal 
 */
class AuthenticationProvider
{
    /**
     * @var AuthenticationMiddlewareInterface[]
     */
    private $middlewares;

    public function __construct()
    {
        $this->middlewares = [];
    }

    /**
     * @param AuthenticationMiddlewareInterface $middleware
     * @param int $priority Priority of middlewares. Bigger number gets higher priority
     */
    public function addMiddleware(AuthenticationMiddlewareInterface $middleware, $priority = 100)
    {
        if (!isset($this->middlewares[$priority])) {
            $this->middlewares[$priority] = [$middleware];
        } else {
            $this->middlewares[$priority][] = $middleware;
        }

        ksort($this->middlewares);
    }

    /**
     * @return \Closure|\Generator
     * @throws AuthenticationConfigurationException
     */
    public function getMiddlewares()
    {
        if (empty($this->middlewares)) {
            return;
        }

        /** @var AuthenticationMiddlewareInterface[] $middlewares */
        $middlewares = call_user_func_array('array_merge', $this->middlewares);

        foreach ($middlewares as $middleware) {
            yield $this->getMiddlewareFunction($middleware);
        }
    }

    /**
     * @param AuthenticationMiddlewareInterface $middleware
     * @return \Closure
     */
    private function getMiddlewareFunction(AuthenticationMiddlewareInterface $middleware)
    {
        return function (callable $handler) use ($middleware) {
            return function ($request, $options) use ($middleware, $handler) {
                return $middleware($handler, $request, $options);
            };
        };
    }
}
