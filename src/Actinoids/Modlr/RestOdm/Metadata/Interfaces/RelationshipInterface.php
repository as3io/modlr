<?php

namespace Actinoids\Modlr\RestOdm\Metadata\Interfaces;

use Actinoids\Modlr\RestOdm\Metadata\RelationshipMetadata;

/**
 * Interface for Metadata objects containing relationships.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface RelationshipInterface extends PropertyInterface
{
    /**
     * Adds a relationship field to the implementing metadata object.
     *
     * @param   RelationshipMetadata    $relationship
     * @return  self
     * @throws  MetadataException       If the relationship key already exists as an attribute.
     */
    public function addRelationship(RelationshipMetadata $relationship);

    /**
     * Gets all relationship fields for the implementing metadata object.
     *
     * @return  RelationshipMetadata[]
     */
    public function getRelationships();

    /**
     * Determines if a relationship field exists on the implementing object.
     *
     * @param   string  $key
     * @return  bool
     */
    public function hasRelationship($key);

    /**
     * Gets a relationship field from the implementing object.
     * Returns null if the relationship does not exist.
     *
     * @param   string  $key
     * @return  RelationshipMetadata|null
     */
    public function getRelationship($key);
}
