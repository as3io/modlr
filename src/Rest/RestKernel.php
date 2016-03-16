<?php

namespace As3\Modlr\Rest;

use As3\Modlr\Api\AdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST Kernel.
 * Handles incoming Requests, converts them to REST request format, and handles them via the Adapter.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class RestKernel
{
    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * @var RestConfiguration
     */
    private $config;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * Constructor.
     *
     * @param   AdapterInterface    $adapter
     * @param   RestConfiguration   $config
     */
    public function __construct(AdapterInterface $adapter, RestConfiguration $config)
    {
        $this->adapter = $adapter;
        $this->config = $config;
    }

    /**
     * Enables/disables debug.
     *
     * @param   bool    $debug
     * @return  self
     */
    public function enableDebug($debug = true)
    {
        $this->debug = (bool) $debug;
        return $this;
    }

    /**
     * Processes an incoming Request object, routes it to the adapter, and returns a response.
     *
     * @param   Request     $request
     * @return  Response    $response
     */
    public function handle(Request $request)
    {
        try {
            $restRequest = new RestRequest($this->config, $request->getMethod(), $request->getUri(), $request->getContent());
            $restResponse = $this->adapter->processRequest($restRequest);
        } catch (\Exception $e) {
            if (true === $this->debugEnabled()) {
                throw $e;
            }
            $restResponse = $this->adapter->handleException($e);
        }
        return new Response($restResponse->getContent(), $restResponse->getStatus(), $restResponse->getHeaders());
    }

    /**
     * Whether debug is enabled.
     *
     * @return  bool
     */
    public function debugEnabled()
    {
        return $this->debug;
    }
}
