<?php

namespace Actinoids\Modlr\RestOdm\Metadata\Traits;

use Actinoids\Modlr\RestOdm\Exception\MetadataException;
use Actinoids\Modlr\RestOdm\Metadata\AttributeMetadata;
use Actinoids\Modlr\RestOdm\Metadata\RelationshipMetadata;

/**
 * Common property (attribute and relationship) metadata get, set, and add methods.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
trait PropertiesTrait
{
    /**
     * All attribute fields assigned to this metadata object.
     * An attribute is a "standard" field, such as a string, integer, array, etc.
     *
     * @var AttributeMetadata[]
     */
    public $attributes = [];

    /**
     * All relationship fields assigned to this metadata object.
     * A relationship is a field that relates to another entity.
     *
     * @var RelationshipMetadata[]
     */
    public $relationships = [];

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

    /**
     * Adds a relationship field to this entity.
     *
     * @param   RelationshipMetadata    $relationship
     * @return  self
     * @throws  MetadataException       If the relationship key already exists as an attribute.
     */
    public function addRelationship(RelationshipMetadata $relationship)
    {
        if (isset($this->attributes[$relationship->getKey()])) {
            throw MetadataException::fieldKeyInUse('relationship', 'attribute', $relationship->getKey(), $this->type);
        }
        $this->relationships[$relationship->getKey()] = $relationship;
        ksort($this->relationships);
        return $this;
    }

    /**
     * Gets all relationship fields for this entity.
     *
     * @return  RelationshipMetadata[]
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * Determines if a relationship field exists on this entity.
     *
     * @param   string  $key
     * @return  bool
     */
    public function hasRelationship($key)
    {
        return null !== $this->getRelationship($key);
    }

    /**
     * Gets a relationship field from this entity.
     * Returns null if the relationship does not exist.
     *
     * @param   string  $key
     * @return  RelationshipMetadata|null
     */
    public function getRelationship($key)
    {
        if (!isset($this->relationships[$key])) {
            return null;
        }
        return $this->relationships[$key];
    }
}
