<?php

namespace Paysera\Component\RestClientCommon\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RequestException extends \Exception
{
    private $request;
    private $response;

    /**
     * @param string $message
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     * @param \Exception|null $previous
     */
    public function __construct(
        $message,
        RequestInterface $request,
        ResponseInterface $response = null,
        \Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return null|ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    public static function create(RequestInterface $request, ResponseInterface $response = null)
    {
        return new static(null, $request, $response);
    }
}
