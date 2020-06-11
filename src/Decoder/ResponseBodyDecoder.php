<?php

namespace Paysera\Component\RestClientCommon\Decoder;

use Paysera\Component\RestClientCommon\Decoder\ResponseDecoder\ResponseDecoderInterface;
use Paysera\Component\RestClientCommon\Exception\UnsupportedContentTypeException;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
class ResponseBodyDecoder
{
    /**
     * @var ResponseDecoderInterface[]
     */
    private $decoders;

    public function __construct()
    {
        $this->decoders = [];
    }

    /**
     * @param ResponseDecoderInterface $decoder
     * @param string $contentType
     */
    public function addDecoder(ResponseDecoderInterface $decoder, $contentType)
    {
        $this->decoders[$contentType] = $decoder;
    }

    /**
     * @param string $contentType
     * @param ResponseInterface $response
     * @return array
     * @throws UnsupportedContentTypeException
     */
    public function decodeContent($contentType, ResponseInterface $response)
    {
        if (isset($this->decoders[$contentType])) {
            return $this->decoders[$contentType]->decode($response);
        }

        throw new UnsupportedContentTypeException(sprintf('No ResponseDecoder for type "%s" found', $contentType));
    }
}
