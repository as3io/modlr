<?php

namespace Actinoids\Modlr\RestOdm\Rest;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Actinoids\Modlr\RestOdm\Adapter\AdapterInterface;

/**
 * REST Kernel.
 * Handles incoming Requests, converts them to REST request format, and handles them via the Adapter.
 *
 * @todo    CORS would need to be implemented by account.
 * @todo    Account API key would need to be parsed in order to establish Org and Project.
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
     * Processes an incoming Request object, routes it to the adapter, and returns a response.
     *
     * @todo    The adapter needs to validate that the core Request object is valid. Ensure JSON, etc.
     * @param   Request     $request
     * @return  Response    $response
     */
    public function handle(Request $request)
    {
        try {
            $restRequest = new RestRequest($this->config, $request->getMethod(), $request->getUri(), $request->getContent());
            $restResponse = $this->adapter->processRequest($restRequest);
        } catch (\Exception $e) {
            $restResponse = $this->adapter->handleException($e);
        }
        return new Response($restResponse->getContent(), $restResponse->getStatus(), $restResponse->getHeaders());
    }
}
