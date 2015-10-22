<?php

namespace Actinoids\Modlr\RestOdm\Metadata;

use Actinoids\Modlr\RestOdm\Exception\MetadataException;

/**
 * Defines the metadata for an entity (e.g. a database object).
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class EntityMetadata implements AttributeInterface
{
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
     * The database name.
     *
     * @var string
     */
    public $db;

    /**
     * The collection name.
     *
     * @var string
     */
    public $collection;

    /**
     * The ID strategy to use.
     * Currently object is the only valid choice.
     *
     * @todo Implement an auto-increment integer id strategy.
     * @var string
     */
    public $idStrategy = 'object';

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
     * All attribute fields assigned to this entity.
     * An attribute is a "standard" field, such as a string, integer, array, etc.
     *
     * @var AttributeMetadata[]
     */
    public $attributes = [];

    /**
     * All relationship fields assigned to this entity.
     * A relationship is a field that relates to another entity.
     *
     * @var RelationshipMetadata[]
     */
    public $relationships = [];

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
     * Merges an EntityMetadata instance with this instance.
     * For use with entity class extension.
     * Only merge items where you want the child class to override the parent!
     *
     * @param   EntityMetadata  $metadata
     * @return  self
     */
    public function merge(EntityMetadata $metadata)
    {
        $this->setType($metadata->type);
        // @todo Remove these if necessary.
        $this->setPolymorphic($metadata->isPolymorphic());
        $this->setAbstract($metadata->isAbstract());
        $this->extends = $metadata->extends;
        $this->ownedTypes = $metadata->ownedTypes;
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
            throw MetadataException::invalidEntityType();
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
     * Adds an attribute field to this entity.
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
     * Gets all attribute fields for this entity.
     *
     * @return  AttributeMetadata[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Determines any attribute fields exist on this entity.
     *
     * @return  bool
     */
    public function hasAttributes()
    {
        return !empty($this->attributes);
    }

    /**
     * Determines if an attribute field exists on this entity.
     *
     * @param   string  $key
     * @return  bool
     */
    public function hasAttribute($key)
    {
        return null !== $this->getAttribute($key);
    }

    /**
     * Gets an attribute field from this entity.
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
