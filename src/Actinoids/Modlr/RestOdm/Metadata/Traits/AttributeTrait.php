<?php

namespace Actinoids\Modlr\RestOdm\Metadata\Traits;

use Actinoids\Modlr\RestOdm\Exception\MetadataException;
use Actinoids\Modlr\RestOdm\Metadata\AttributeMetadata;

/**
 * Common attribute metadata get, set, and add methods.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
trait AttributeTrait
{
    /**
     * All attribute fields assigned to this entity.
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
     * @throws  MetadataException   If the attribute key already exists as a relationship.
     */
    public function addAttribute(AttributeMetadata $attribute)
    {
        if (isset($this->relationships[$attribute->getKey()])) {
            throw MetadataException::fieldKeyInUse('attribute', 'relationship', $attribute->getKey(), $this->type);
        }
        $this->attributes[$attribute->getKey()] = $attribute;
        ksort($this->attributes);
        return $this;
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
     * Determines any attribute fields exist.
     *
     * @return  bool
     */
    public function hasAttributes()
    {
        return !empty($this->attributes);
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
}
