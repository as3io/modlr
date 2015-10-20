<?php

namespace Actinoids\Modlr\RestOdm\Rest;

use Actinoids\Modlr\RestOdm\Util\Validator;

/**
 * REST Configuration.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class RestConfiguration
{
    const ROOT_ENDPOINT = '/1.0';
    const EXTENSION_DELIM = '__';

    /**
     * Validator service for handling common validation tasks.
     *
     * @var Validator
     */
    private $validator;

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
    private $entityFormat = 'studlycaps';

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
        return self::ROOT_ENDPOINT;
    }

    /**
     * Sets the entity type format.
     *
     * @param   string  $format
     * @return  self
     */
    public function setEntityFormat($format)
    {
        $this->validator->validateStringFormat($format);
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
        $this->validator->validateStringFormat($format);
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
     * Gets the model extension delimiter.
     *
     * @return  string
     */
    public function getExtensionDelimiter()
    {
        self::EXTENSION_DELIM;
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
