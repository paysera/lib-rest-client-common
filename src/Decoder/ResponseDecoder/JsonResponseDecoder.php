<?php

namespace Paysera\Component\RestClientCommon\Decoder\ResponseDecoder;

use Psr\Http\Message\ResponseInterface;

/**
 * @internal 
 */
class JsonResponseDecoder implements ResponseDecoderInterface
{
    public function decode(ResponseInterface $response)
    {
        return \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
    }
}
