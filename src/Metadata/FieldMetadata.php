<?php

namespace As3\Modlr\Metadata;

use As3\Modlr\Exception\MetadataException;

/**
 * Abstract field metadata class used for all Entity fields (attributes and relationships).
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class FieldMetadata
{
    /**
     * A friendly description of the field.
     *
     * @var string
     */
    public $description;

    /**
     * The field key.
     *
     * @var string
     */
    public $key;

    /**
     * Determines if this field came from a mixin.
     *
     * @var bool
     */
    public $mixin;

    /**
     * Whether this property should be persisted.
     *
     * @var bool
     */
    public $save = true;

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
     * Enables or disables saving of this property.
     *
     * @param   bool    $bit
     * @return  self
     */
    public function enableSave($bit = true)
    {
        $this->save = (bool) $bit;
        return $this;
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
     * Determines whether this propety is stored in search.
     *
     * @return  bool
     */
    public function isSearchProperty()
    {
        return $this->searchProperty;
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
     * Whether this property should be saved/persisted to the data layer.
     *
     * @return  bool
     */
    public function shouldSave()
    {
        return $this->save;
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
