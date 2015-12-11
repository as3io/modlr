<?php

namespace Actinoids\Modlr\RestOdm\Metadata;

use Actinoids\Modlr\RestOdm\Exception\MetadataException;

/**
 * Defines metadata for a relationship field.
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class RelationshipMetadata extends FieldMetadata
{
    /**
     * The entity type this is related to.
     *
     * @var string
     */
    public $entityType;

    /**
     * The relationship type: one or many
     *
     * @var string
     */
    public $relType;

    /**
     * Determines if this is an inverse (non-owning) relationship.
     *
     * @var bool
     */
    public $isInverse = false;

    /**
     * The inverse field.
     *
     * @var bool
     */
    public $inverseField;

    /**
     * Determines if the relationship related to a polymorphic entity.
     *
     * @var bool
     */
    public $polymorphic = false;

    /**
     * Child entity types the related entity owns.
     * Only used for polymorphic relationships.
     */
    public $ownedTypes = [];

    /**
     * Constructor.
     *
     * @param   string  $key        The relationship field key.
     * @param   string  $relType    The relationship type.
     * @param   string  $entityType The entity type key.
     * @param   bool    $mixin
     */
    public function __construct($key, $relType, $entityType, $mixin = false)
    {
        parent::__construct($key, $mixin);
        $this->setRelType($relType);
        $this->entityType = $entityType;
    }

    /**
     * Gets the entity type that this field is related to.
     *
     * @return  string
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * Gets the relationship type.
     *
     * @return  string
     */
    public function getRelType()
    {
        return $this->relType;
    }

    /**
     * Determines if this is a one (single) relationship.
     *
     * @return  bool
     */
    public function isOne()
    {
        return 'one' === $this->getRelType();
    }

    /**
     * Determines if this is a many relationship.
     *
     * @return bool
     */
    public function isMany()
    {
        return 'many' === $this->getRelType();
    }

    public function isPolymorphic()
    {
        return $this->polymorphic;
    }

    public function setPolymorphic($bit = true)
    {
        $this->polymorphic = (Boolean) $bit;
        return $this;
    }

    /**
     * Sets the relationship type: one or many.
     *
     * @param   string  $relType
     * @return  self
     */
    public function setRelType($relType)
    {
        $this->validateType($relType);
        $this->relType = $relType;
        return $this;
    }

    /**
     * Validates the relationship type.
     *
     * @param   string  $type
     * @return  bool
     * @throws  MetadataException
     */
    protected function validateType($relType)
    {
        $valid = ['one', 'many'];
        if (!in_array($relType, $valid)) {
            throw MetadataException::invalidRelType($relType, $valid);
        }
        return true;
    }
}
