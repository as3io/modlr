<?php

namespace As3\Modlr\Rest;

use As3\Modlr\Util\Validator;

/**
 * REST Configuration.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class RestConfiguration
{
    /**
     * @var string
     */
    private $rootEndpoint = '/';

    /**
     * Validator service for handling common validation tasks.
     *
     * @var Validator
     */
    private $validator;

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
     * @var bool
     */
    private $includeAll = true;

    /**
     * Determines how entity names should be formatted.
     *
     * @var string
     */
    private $entityFormat = 'dash';

    /**
     * Determines how field key names should be formatted.
     *
     * @var string
     */
    private $fieldKeyFormat = 'camelcase';

    /**
     * Constructor.
     *
     * @param   Validator|null  $validator
     */
    public function __construct(Validator $validator = null)
    {
        $this->validator = $validator ?: new Validator();
    }

    /**
     * Gets the validator service.
     *
     * @return  Validator
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Gets the root API endpoint shared by all requests.
     *
     * @return  string
     */
    public function getRootEndpoint()
    {
        return $this->rootEndpoint;
    }

    /**
     * Sets the root API endpoint shared by all requests.
     *
     * @param   string  $endpoint
     * @return  self
     */
    public function setRootEndpoint($endpoint)
    {
        $this->rootEndpoint = sprintf('/%s', trim($endpoint, '/'));
        return $this;
    }

    /**
     * Sets the entity type format.
     *
     * @param   string  $format
     * @return  self
     */
    public function setEntityFormat($format)
    {
        $this->validator->isFormatValid($format);
        $this->entityFormat = $format;
        return $this;
    }

    /**
     * Gets the entity type format.
     *
     * @return  string
     */
    public function getEntityFormat()
    {
        return $this->entityFormat;
    }

    /**
     * Sets the field key format.
     *
     * @param   string  $format
     * @return  self
     */
    public function setFieldKeyFormat($format)
    {
        $this->validator->isFormatValid($format);
        $this->fieldKeyFormat = $format;
        return $this;
    }

    /**
     * Gets the field key format.
     *
     * @return  string
     */
    public function getFieldKeyFormat()
    {
        return $this->fieldKeyFormat;
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
            'limit'     => 25,
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
