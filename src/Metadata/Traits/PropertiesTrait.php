<?php

namespace As3\Modlr\RestOdm\Metadata\Traits;

use As3\Modlr\RestOdm\Exception\MetadataException;
use As3\Modlr\RestOdm\Metadata\AttributeMetadata;
use As3\Modlr\RestOdm\Metadata\FieldMetadata;
use As3\Modlr\RestOdm\Metadata\RelationshipMetadata;

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
     * Gets all properties: attributes + relationships.
     *
     * @return  FieldMetadata[]
     */
    public function getProperties()
    {
        return array_merge($this->getAttributes(), $this->getRelationships());
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

    /**
     * Determines whether search is enabled.
     *
     * @return  bool
     */
    public function isSearchEnabled()
    {
        $propertes = $this->getSearchProperties();
        return !empty($propertes);
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
     * Gets all properties that are flagged for storage in search.
     *
     * @return  FieldMetadata[]
     */
    public function getSearchProperties()
    {
        static $props;
        if (null !== $props) {
            return $props;
        }

        $props = [];
        foreach ($this->getProperties() as $key => $property) {
            if (false === $property->isSearchProperty()) {
                continue;
            }
            $props[$key] = $property;
        }
        return $props;
    }

    /**
     * Determines if a property (attribute or relationship) is indexed for search.
     *
     * @param   string  $key    The property key.
     * @return  bool
     */
    public function propertySupportsSearch($key)
    {
        return isset($this->getSearchProperties()[$key]);
    }
}
