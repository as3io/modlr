<?php

namespace Actinoids\Modlr\RestOdm\Rest;

/**
 * REST Configuration.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class RestConfiguration
{
    // @todo Should be determined by the server/version config
    const ROOT_ENDPOINT = '/api/1.0';
    const NS_DELIM_EXTERNAL = '_';
    const NS_DELIM_INTERNAL = '\\';

    /**
     * The configured API scheme, such as http or https.
     *
     * @var string
     */
    private $scheme;

    /**
     * The configured API hostname.
     *
     * @var string
     */
    private $host;

    /**
     * Whether all relationship fields should be included by default.
     *
     * @param   bool
     */
    private $includeAll = true;

    /**
     * Gets the root API endpoint shared by all requests.
     *
     * @return  string
     */
    public function getRootEndpoint()
    {
        return self::ROOT_ENDPOINT;
    }

    /**
     * Gets the internal entity type namespace delimiter.
     *
     * @return  string
     */
    public function getInternalNamespaceDelim()
    {
        return self::NS_DELIM_INTERNAL;
    }

    /**
     * Gets the external entity type namespace delimiter.
     *
     * @return  string
     */
    public function getExternalNamespaceDelim()
    {
        return self::NS_DELIM_EXTERNAL;
    }

    /**
     * Sets the scheme for all API requests.
     *
     * @param   string  $scheme
     * @return  self
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * Gets the scheme for all API requests.
     *
     * @return  string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Sets the hostname for all API requests.
     *
     * @param   string  $host
     * @return  self
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Gets the hostname for all API requests.
     *
     * @return  string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Whether all relationships should be included (side-loaded) by default.
     *
     * @param   bool
     */
    public function includeAllByDefault()
    {
        return $this->includeAll;
    }

    /**
     * Gets the default pagination criteria.
     *
     * @return  array
     */
    public function getDefaultPagination()
    {
        return [
            'offset'    => 0,
            'limit'     => 50,
        ];
    }

    /**
     * Gets the default sorting criteria.
     *
     * @return  array
     */
    public function getDefaultSorting()
    {
        return ['id' => -1];
    }
}
