<?php

namespace Actinoids\Modlr\RestOdm\Metadata;

use Actinoids\Modlr\RestOdm\Exception\MetadataException;

/**
 * Abstract field metadata class used for all Entity fields (attributes and relationships).
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class FieldMetadata
{
    /**
     * The field key.
     *
     * @var string
     */
    public $key;

    /**
     * A friendly description of the field.
     *
     * @var string
     */
    public $description;

    /**
     * Determines if this field came from a mixin.
     *
     * @var bool
     */
    public $mixin;

    /**
     * Determines whether this propety is stored in search.
     *
     * @var bool
     */
    public $searchProperty = false;

    /**
     * Constructor.
     *
     * @param   string  $key
     * @param   bool    $mixin
     */
    public function __construct($key, $mixin = false)
    {
        $this->validateKey($key);
        $this->mixin = (Boolean) $mixin;
        $this->key = $key;
    }

    /**
     * Gets the field key.
     *
     * @return  string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Sets whether this property is stored in search.
     *
     * @param   bool    $bit
     * @return  self
     */
    public function setSearchProperty($bit = true)
    {
        $this->searchProperty = (Boolean) $bit;
        return $this;
    }

    /**
     * Determines whether this propety is stored in search.
     *
     * @return  bool
     */
    public function isSearchProperty()
    {
        return $this->searchProperty;
    }

    /**
     * Validates that the field key is not reserved.
     *
     * @param   string  $key
     * @throws  MetadataException
     */
    protected function validateKey($key)
    {
        $reserved = ['type', 'id'];
        if (in_array(strtolower($key), $reserved)) {
            throw MetadataException::reservedFieldKey($key, $reserved);
        }
    }
}
