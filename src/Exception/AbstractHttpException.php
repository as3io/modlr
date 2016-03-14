<?php

namespace As3\Modlr\Exception;

/**
 * AbstractHttpException.
 * Can be extended to provide support for HTTP friendly response codes.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class AbstractHttpException extends \Exception implements HttpExceptionInterface
{
    /**
     * The HTTP response code.
     *
     * @param int
     */
    protected $httpCode;

    /**
     * The HTTP error type: usually the Exception calling method.
     *
     * @param string
     */
    protected $errorType;

    /**
     * Constructor.
     * Overwritten to require a message and an HTTP code.
     *
     * @param   string                          $message
     * @param   int                             $httpCode
     * @param   string                          $errorType
     * @param   int                             $code
     * @param   HttpExceptionInterface|null     $previous
     */
    public function __construct($detail, $httpCode, $errorType, $code = 0, HttpExceptionInterface $previous = null)
    {
        parent::__construct($detail, $code, $previous);
        $this->httpCode = (Integer) $httpCode;
        $this->errorType = $errorType;
    }

    /**
     * {@inheritDoc}
     */
    public function getErrorType()
    {
        return $this->errorType;
    }

    /**
     * {@inheritDoc}
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }
}
