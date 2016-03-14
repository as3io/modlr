<?php

namespace As3\Modlr\RestOdm\Rest;

/**
 * REST Response object.
 * Is created by an API adapter after processing a REST Request.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class RestResponse
{
    /**
     * The HTTP status code, such as 200.
     *
     * @var int
     */
    private $status;

    /**
     * The response payload, if set.
     *
     * @var RestPayload|null
     */
    private $payload;

    /**
     * Response headers.
     *
     * @var array
     */
    private $headers = [];

    /**
     * Constructor.
     *
     * @param   int                 $status
     * @param   RestPayload|null    $payload
     */
    public function __construct($status, RestPayload $payload = null)
    {
        $this->status = (Integer) $status;
        $this->payload = $payload;
    }

    /**
     * Adds a response header.
     *
     * @param   string  $name
     * @param   string  $value
     * @return  self
     */
    public function addHeader($name, $value)
    {
        $name = strtolower($name);
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Sets an array of response headers.
     *
     * @param   array   $headers
     * @return  self
     */
    public function setHeaders(array $headers)
    {
        foreach ($this->headers as $name => $value) {
            $this->addHeader($name, $value);
        }
        return $this;
    }

    /**
     * Gets the response headers.
     *
     * @return  array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Gets the HTTP status code.
     *
     * @return  int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Gets the response payload.
     *
     * @return  RestPayload|null
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Gets the response content, from the payload.
     *
     * @return  string|null
     */
    public function getContent()
    {
        if (null === $this->getPayload()) {
            return null;
        }
        return $this->getPayload()->getData();
    }
}
