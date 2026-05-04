<?php

declare(strict_types=1);

namespace Paysera\Component\RestClientCommon\Middleware;

interface GuzzleMiddlewareProviderInterface
{
    public function getMiddleware(): callable;
}
