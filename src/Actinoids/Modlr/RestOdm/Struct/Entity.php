<?php

namespace Actinoids\Modlr\RestOdm\Struct;

class Entity extends Identifier
{
    /**
     * Attribute objects assigned to the entity.
     *
     * @var Attribute[]
     */
    protected $attributes = [];

    /**
     * Relationship objects assigned to the entity.
     *
     * @var Relationship[]
     */
    protected $relationships = [];

    /**
     * Gets all attribute objects of this entity.
     *
     * @return  Attribute[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Adds an attribute to this entity.
     *
     * @param   Attribute   $attribute
     * @return  self
     */
    public function addAttribute(Attribute $attribute)
    {
        $this->attributes[$attribute->getKey()] = $attribute;
        return $this;
    }

    /**
     * Gets an attribute from this entity.
     * Returns null if the attribute doesn't exist.
     *
     * @param   string  $key
     * @return  Attribute|null
     */
    public function getAttribute($key)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }
        return null;
    }

    /**
     * Determines if an attribute exists on this entity.
     *
     * @param   string  $key
     * @return  bool
     */
    public function hasAttribute($key)
    {
        return null !== $this->getAttribute($key);
    }

    /**
     * Gets all relationship objects of this entity.
     *
     * @return  Relationship[]
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * Adds a relationship to this entity.
     *
     * @param   Relationship   $relationship
     * @return  self
     */
    public function addRelationship(Relationship $relationship)
    {
        $this->relationships[$relationship->getKey()] = $relationship;
        return $this;
    }

    /**
     * Gets a relationship from this entity.
     * Returns null if the relationship doesn't exist.
     *
     * @param   string  $key
     * @return  Relationship|null
     */
    public function getRelationship($key)
    {
        if (isset($this->relationships[$key])) {
            return $this->relationships[$key];
        }
        return null;
    }

    /**
     * Determines if a relationship exists on this entity.
     *
     * @param   string  $key
     * @return  bool
     */
    public function hasRelationship($key)
    {
        return null !== $this->getRelationship($key);
    }
}
