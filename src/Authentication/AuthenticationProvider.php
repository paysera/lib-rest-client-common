<?php

namespace Paysera\Component\RestClientCommon\Authentication;

use Paysera\Component\RestClientCommon\Authentication\Exception\AuthenticationConfigurationException;
use Paysera\Component\RestClientCommon\Authentication\Middleware\AuthenticationMiddlewareInterface;

class AuthenticationProvider
{
    const HANDLER_POSITION = 'prepare_body';

    /**
     * @var AuthenticationMiddlewareInterface[]
     */
    private $middlewares;

    public function __construct()
    {
        $this->middlewares = [];
    }

    public function addMiddleware(AuthenticationMiddlewareInterface $middleware)
    {
        $this->middlewares[] = $middleware;
        usort(
            $this->middlewares,
            function (AuthenticationMiddlewareInterface $a, AuthenticationMiddlewareInterface $b) {
                if ($a->getPriority() === $b->getPriority()) {
                    return 0;
                }
                return $a->getPriority() < $b->getPriority() ? 1 : -1;
            }
        );
    }

    /**
     * @return \Closure|\Generator
     * @throws AuthenticationConfigurationException
     */
    public function getMiddlewares()
    {
        foreach ($this->middlewares as $middleware) {
            yield function (callable $handler) use ($middleware) {
                return function ($request, $options) use ($middleware, $handler) {
                    return $middleware($handler, $request, $options);
                };
            };
        }
    }
}
