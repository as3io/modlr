<?php

namespace Actinoids\Modlr\RestOdm\Metadata;

use Actinoids\Modlr\RestOdm\Exception\MetadataException;
use Actinoids\Modlr\RestOdm\Metadata\Interfaces\AttributeInterface;
use Actinoids\Modlr\RestOdm\Metadata\Interfaces\MergeableInterface;
use Actinoids\Modlr\RestOdm\Metadata\Interfaces\PersistenceInterface;
use Actinoids\Modlr\RestOdm\Metadata\Interfaces\RelationshipInterface;

/**
 * Defines the metadata for an entity (e.g. a database object).
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class EntityMetadata implements AttributeInterface, RelationshipInterface, MergeableInterface
{
    use Traits\PropertiesTrait;

    /**
     * The id key name and type.
     */
    const ID_KEY  = 'id';
    const ID_TYPE = 'string';

    /**
     * Uniquely defines the type of entity.
     *
     * @var string
     */
    public $type;

    /**
     * Whether this class is considered polymorphic.
     *
     * @var bool
     */
    public $polymorphic = false;

    /**
     * Child entity types this entity owns.
     * Only used for polymorphic entities.
     */
    public $ownedTypes = [];

    /**
     * The entity type this entity extends.
     *
     * @var bool
     */
    public $extends;

     /**
     * Whether this class is abstract.
     *
     * @var bool
     */
    public $abstract = false;

    /**
     * The persistence metadata for this entity.
     *
     * @param PersistenceInterface
     */
    public $persistence;

    /**
     * All mixins assigned to this entity.
     *
     * @todo    Implement this.
     * @var     MixinMetadata[]
     */
    public $mixins = [];

    /**
     * Constructor.
     *
     * @param   string  $type   The resource identifier type.
     */
    public function __construct($type)
    {
        $this->setType($type);
    }

    /**
     * {@inheritDoc}
     */
    public function merge(MergeableInterface $metadata)
    {
        $this->setType($metadata->type);
        $this->setPolymorphic($metadata->isPolymorphic());
        $this->setAbstract($metadata->isAbstract());
        $this->extends = $metadata->extends;
        $this->ownedTypes = $metadata->ownedTypes;

        $this->persistence->merge($metadata->persistence);
        $this->mergeAttributes($metadata->getAttributes());
        $this->mergeRelationships($metadata->getRelationships());

        // @todo Implement this.
        // $this->mergeMixins($metadata->getMixins());
        return $this;
    }

    /**
     * Sets the entity type.
     *
     * @param   string  $type
     * @return  self
     * @throws  MetadataException   If the type is not a string or is empty.
     */
    public function setType($type)
    {
        if (!is_string($type) || empty($type)) {
            throw MetadataException::invalidEntityType($type);
        }
        $this->type = $type;
        return $this;
    }

    /**
     * Merges attributes with this instance's attributes.
     *
     * @param   array   $toAdd
     * @return  self
     */
    private function mergeAttributes(array $toAdd)
    {
        foreach ($toAdd as $attribute) {
            $this->addAttribute($attribute);
        }
        return $this;
    }

    /**
     * Merges relationships with this instance's relationships.
     *
     * @param   array   $toAdd
     * @return  self
     */
    private function mergeRelationships(array $toAdd)
    {
        foreach ($toAdd as $relationship) {
            $this->addRelationship($relationship);
        }
        return $this;
    }

    /**
     * Whether this metadata represents a polymorphic class.
     *
     * @return  bool
     */
    public function isPolymorphic()
    {
        return (Boolean) $this->polymorphic;
    }

    /**
     * Sets this metadata as representing a polymorphic class.
     *
     * @param   bool    $bit
     * @return  self
     */
    public function setPolymorphic($bit = true)
    {
        $this->polymorphic = (Boolean) $bit;
        return $this;
    }

    /**
     * Whether this metadata represents an abstract class.
     *
     * @return  bool
     */
    public function isAbstract()
    {
        return (Boolean) $this->abstract;
    }

    /**
     * Sets this metadata as representing an abstract class.
     *
     * @param   bool    $bit
     * @return  self
     */
    public function setAbstract($bit = true)
    {
        $this->abstract = (Boolean) $bit;
        return $this;
    }

    /**
     * Determines if this is a child entity of another entity.
     *
     * @return  bool
     */
    public function isChildEntity()
    {
        return null !== $this->getParentEntityType();
    }

    /**
     * Gets the parent entity type.
     * For entities that are extended.
     *
     * @return  string|null
     */
    public function getParentEntityType()
    {
        return $this->extends;
    }

    /**
     * Sets the persistence metadata for this entity.
     *
     * @param   PersisterInterface  $persistence
     * @return  self
     */
    public function setPersistence(PersistenceInterface $persistence)
    {
        $this->persistence = $persistence;
        return $this;
    }
}
