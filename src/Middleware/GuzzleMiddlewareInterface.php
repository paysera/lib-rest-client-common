<?php

declare(strict_types=1);

namespace Paysera\Component\RestClientCommon\Middleware;

interface GuzzleMiddlewareInterface
{
    public function getMiddlewareFunction(): callable;
}
