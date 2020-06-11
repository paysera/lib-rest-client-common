<?php

namespace Paysera\Component\RestClientCommon\Decoder\ResponseDecoder;

use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
interface ResponseDecoderInterface
{
    /**
     * @param ResponseInterface $response
     * @return array
     */
    public function decode(ResponseInterface $response);
}
