<?php

namespace As3\Modlr\Metadata;

/**
 * Defines metadata for a "standard" field.
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class AttributeMetadata extends FieldMetadata
{
    /**
     * The attribute type, such as string, integer, float, etc.
     *
     * @var string
     */
    public $dataType;

    /**
     * The attribute's default value, if set.
     *
     * @var mixed
     */
    public $defaultValue;

    /**
     * Contains the caculated field parameters.
     *
     * @var array
     */
    public $calculated = [
        'class'     => null,
        'method'    => null,
    ];

    /**
     * Whether this attribute is flagged to be stored as an autocomplete field in search.
     *
     * @var bool
     */
    public $autocomplete = false;

    /**
     * Constructor.
     *
     * @param   string  $key        The attribute field key.
     * @param   string  $dataType   The attribute data type.
     * @param   bool    $mixin
     */
    public function __construct($key, $dataType, $mixin = false)
    {
        parent::__construct($key, $mixin);
        $this->dataType = $dataType;
    }

    /**
     * Determines if this attribute has a default value.
     *
     * @return  bool
     */
    public function hasDefaultValue()
    {
        null !== $this->defaultValue;
    }

    /**
     * Determines if this attribute is calculated.
     *
     * @return  bool
     */
    public function isCalculated()
    {
        return null !== $this->calculated['class'] && null !== $this->calculated['method'];
    }

    /**
     * Determines whether this attribute is flagged to be stored as an autocomplete field in search.
     *
     * @return  bool
     */
    public function hasAutocomplete()
    {
        return $this->autocomplete;
    }

    /**
     * Sets whether this attribute will be set as an autocomplete field in search.
     *
     * @param   bool    $bit
     * @return  self
     */
    public function setAutocomplete($bit = true)
    {
        $this->autocomplete = (Boolean) $bit;
        $this->setSearchProperty($bit);
        return $this;
    }
}
