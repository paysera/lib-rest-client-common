<?php

namespace Paysera\Component\RestClientCommon\Exception;

use Exception;
use RuntimeException;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @api
 */
class RequestException extends Exception
{
    private $request;
    private $response;
    private $error;
    private $errors;
    private $errorProperties;
    private $errorDescription;

    /**
     * @param string $message
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param Exception|null $previous
     */
    public function __construct(
        $message,
        RequestInterface $request,
        ResponseInterface $response,
        Exception $previous = null
    ) {
        $message = ($message === null ? '' : $message);
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
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return null|string
     */
    public function getError()
    {
        return $this->error;
    }

    private function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * @return null|array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    private function setErrors($errors)
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getErrorDescription()
    {
        return $this->errorDescription;
    }

    private function setErrorDescription($errorDescription)
    {
        $this->errorDescription  = $errorDescription;

        return $this;
    }

    /**
     * @return null|array
     */
    public function getErrorProperties()
    {
        return $this->errorProperties;
    }

    private function setErrorProperties($errorProperties)
    {
        $this->errorProperties = $errorProperties;

        return $this;
    }

    public static function create(RequestInterface $request, ResponseInterface $response)
    {
        $exception = new static(null, $request, $response);

        if (!$response->getBody()->isReadable()) {
            return $exception;
        }

        try {
            $decodedResponse = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
        } catch (RuntimeException $runtimeException) {
            return $exception;
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $exception;
        } finally {
            if ($response->getBody()->isSeekable()) {
                $response->getBody()->rewind();
            }
        }

        if (isset($decodedResponse['error'])) {
            $exception->setError($decodedResponse['error']);
        }
        if (isset($decodedResponse['errors'])) {
            $exception->setErrors($decodedResponse['errors']);
        }
        if (isset($decodedResponse['error_description'])) {
            $exception->setErrorDescription($decodedResponse['error_description']);
        }
        if (isset($decodedResponse['error_properties'])) {
            $exception->setErrorProperties($decodedResponse['error_properties']);
        }

        return $exception;
    }
}
