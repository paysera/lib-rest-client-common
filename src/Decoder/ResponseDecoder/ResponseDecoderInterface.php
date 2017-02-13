<?php

namespace Paysera\Component\RestClientCommon\Decoder\ResponseDecoder;

use Psr\Http\Message\ResponseInterface;

interface ResponseDecoderInterface
{
    /**
     * @param ResponseInterface $response
     * @return array
     */
    public function decode(ResponseInterface $response);
}
