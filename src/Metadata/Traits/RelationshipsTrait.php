<?php

namespace As3\Modlr\Metadata\Traits;

use As3\Modlr\Exception\MetadataException;
use As3\Modlr\Metadata\RelationshipMetadata;

/**
 * Common relationship metadata get, set, and add methods.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
trait RelationshipsTrait
{
    /**
     * All relationship fields assigned to this metadata object.
     * A relationship is a field that relates to another entity.
     *
     * @var RelationshipMetadata[]
     */
    public $relationships = [];

    /**
     * Adds a relationship field to this entity.
     *
     * @param   RelationshipMetadata    $relationship
     * @return  self
     */
    public function addRelationship(RelationshipMetadata $relationship)
    {
        $this->validateRelationship($relationship);
        $this->relationships[$relationship->getKey()] = $relationship;
        ksort($this->relationships);
        return $this;
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
     * Determines any relationship fields exist.
     *
     * @return  bool
     */
    public function hasRelationships()
    {
        return !empty($this->relationships);
    }

    /**
     * Validates that the relationship can be added.
     *
     * @param   RelationshipMetadata    $relationship
     * @return  self
     * @throws  MetadataException
     */
    abstract protected function validateRelationship(RelationshipMetadata $relationship);
}
