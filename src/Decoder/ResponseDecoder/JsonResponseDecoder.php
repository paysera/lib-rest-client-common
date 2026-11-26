<?php

namespace Paysera\Component\RestClientCommon\Decoder\ResponseDecoder;

use GuzzleHttp\Exception\InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal 
 */
class JsonResponseDecoder implements ResponseDecoderInterface
{
    public function decode(ResponseInterface $response)
    {
        try {
            return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('json_decode error: ' . $exception->getMessage(), 0, $exception);
        }
    }
}
