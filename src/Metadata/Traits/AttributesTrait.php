<?php

namespace As3\Modlr\Metadata\Traits;

use As3\Modlr\Exception\MetadataException;
use As3\Modlr\Metadata\AttributeMetadata;

/**
 * Common attribute metadata get, set, and add methods.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
trait AttributesTrait
{
    /**
     * All attribute fields assigned to this metadata object.
     * An attribute is a "standard" field, such as a string, integer, array, etc.
     *
     * @var AttributeMetadata[]
     */
    public $attributes = [];

    /**
     * Adds an attribute field.
     *
     * @param   AttributeMetadata   $attribute
     * @return  self
     */
    public function addAttribute(AttributeMetadata $attribute)
    {
        $this->validateAttribute($attribute);
        $this->attributes[$attribute->getKey()] = $attribute;
        ksort($this->attributes);
        return $this;
    }

    /**
     * Determines if an attribute supports autocomplete functionality.
     *
     * @param   string  $key    The attribute key.
     * @return  bool
     */
    public function attrSupportsAutocomplete($key)
    {
        return isset($this->getAutocompleteAttributes()[$key]);
    }

    /**
     * Gets an attribute field.
     * Returns null if the attribute does not exist.
     *
     * @param   string  $key
     * @return  AttributeMetadata|null
     */
    public function getAttribute($key)
    {
        if (!isset($this->attributes[$key])) {
            return null;
        }
        return $this->attributes[$key];
    }

    /**
     * Gets all attribute fields.
     *
     * @return  AttributeMetadata[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Gets all properties that are flagged for autocomplete in search.
     *
     * @return  AttributeMetadata[]
     */
    public function getAutocompleteAttributes()
    {
        static $attrs;
        if (null !== $attrs) {
            return $attrs;
        }

        $attrs = [];
        foreach ($this->getAttributes() as $key => $attribute) {
            if (false === $attribute->hasAutocomplete()) {
                continue;
            }
            $attrs[$key] = $attribute;
        }
        return $attrs;
    }

    /**
     * Determines if an attribute field exists.
     *
     * @param   string  $key
     * @return  bool
     */
    public function hasAttribute($key)
    {
        return null !== $this->getAttribute($key);
    }

    /**
     * Determines any attribute fields exist.
     *
     * @return  bool
     */
    public function hasAttributes()
    {
        return !empty($this->attributes);
    }

    /**
     * Validates that the attribute can be added.
     *
     * @param   AttributeMetadata   $attribute
     * @return  self
     * @throws  MetadataException
     */
    abstract protected function validateAttribute(AttributeMetadata $attribute);
}
