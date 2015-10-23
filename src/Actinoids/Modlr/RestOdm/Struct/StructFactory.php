<?php

namespace Actinoids\Modlr\RestOdm\Struct;

use Actinoids\Modlr\RestOdm\Metadata\MetadataFactory;
use Actinoids\Modlr\RestOdm\Exception\InvalidArgumentException;

/**
 * Factory for creating and building a resource object structure, for use in API adapters and persistence stores.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class StructFactory
{
    /**
     * @var MetadataFactory
     */
    private $mf;

    /**
     * Constructor.
     *
     * @param   MetadataFactory   $mf
     */
    public function __construct(MetadataFactory $mf)
    {
        $this->mf = $mf;
    }

    /**
     * Creates a new resource.
     *
     * @param   string  $entityType     The primary entity type this resource represents.
     * @param   string  $resourceType   The resource type: one or many.
     * @return  Document
     */
    public function createResource($entityType, $resourceType)
    {
        return new Resource($entityType, $resourceType);
    }

    /**
     * Creates a new collection.
     *
     * @return  Collection
     */
    public function createCollection()
    {
        return new Collection();
    }

    /**
     * Creates a new entity.
     *
     * @param   string  $type   The entity type.
     * @param   string  $id     The entity unique identifier.
     * @return  Entity
     */
    public function createEntity($type, $id)
    {
        return new Entity($id, $type);
    }

    /**
     * Creates a new entity identifier.
     *
     * @param   string  $type   The entity type.
     * @param   string  $id     The entity unique identifier.
     * @return  Identifier
     */
    public function createEntityIdentifier($type, $id)
    {
        return new Identifier($id, $type);
    }

    /**
     * Applies an entity or entity identifier to a resource.
     *
     * @param   Resource            $document   The primary resource.
     * @param   EntityInterface     $entity     The entity or entity identifier to add.
     * @return  self
     */
    public function applyEntity(Resource $resource, EntityInterface $entity)
    {
        $this->validateResourceTypes($resource->getEntityType(), $entity->getType());
        $resource->pushData($entity);
        return $this;
    }

    /**
     * Applies a Relationship structure object to the owning Entity structure.
     *
     * @param   Entity          $owner
     * @param   Relationship    $relationship
     * @param   EntityInterface $related
     * @return  self
     */
    public function applyRelationship(Entity $owner, Relationship $relationship, EntityInterface $related)
    {
        $this->validateRelationshipOwner($owner->getType(), $relationship->getKey());
        $this->validateResourceTypes($relationship->getEntityType(), $related->getType());
        $relationship->pushData($related);
        $owner->addRelationship($relationship);
        return $this;
    }

    /**
     * Creates a new Relationship structure for an owning entity and relationship field key.
     *
     * @param   Entity  $owner
     * @param   string  $fieldKey
     * @return  Relationship
     */
    public function createRelationship(Entity $owner, $fieldKey)
    {
        $this->validateRelationshipOwner($owner->getType(), $fieldKey);
        $meta = $this->mf->getMetadataForType($owner->getType());
        $relMeta = $meta->getRelationship($fieldKey);
        return new Relationship($fieldKey, $relMeta->getEntityType(), $relMeta->getRelType());

    }

    /**
     * Vaidates that the owning entity has the provided relationship key.
     *
     * @param   string  $owningEntityType
     * @param   string  $fieldKey
     * @return  bool
     * @throws  InvalidArgumentException
     */
    protected function validateRelationshipOwner($owningEntityType, $fieldKey)
    {
        $meta = $this->mf->getMetadataForType($owningEntityType);
        if (false === $meta->hasRelationship($fieldKey)) {
            throw new InvalidArgumentException('The resource "%s" does not contain relationship field "%s"', $owner->getType(), $fieldKey);
        }
        return true;
    }

    /**
     * Applies an array or array-like set of attribute data to an entity.
     * Each array member must be keyed by the attribute field key.
     *
     * @param   Entity              $entity     The entity to apply the attributes to.
     * @param   array|\ArrayAccess  $data       The attribute data to apply.
     * @return  self
     */
    public function applyAttributes(Entity $entity, $data)
    {
        $this->validateData($data);
        $meta = $this->mf->getMetadataForType($entity->getType());
        foreach ($meta->getAttributes() as $key => $attribute) {
            if (!isset($data[$key])) {
                continue;
            }
            $this->applyAttribute($entity, $key, $data[$key]);
        }
        return $this;
    }

    /**
     * Applies a single attribute value to a resource.
     *
     * @param   Entity      $entity     The entity to apply the attribute value to.
     * @param   string      $fieldKey   The attribute field key.
     * @param   mixed       $value      The attribute value.
     * @return  self
     */

    public function applyAttribute(Entity $entity, $fieldKey, $value)
    {
        $entity->addAttribute($this->createAttribute($fieldKey, $value));
        return $this;
    }

    /**
     * Validates that a data set is an array or is array-like.
     *
     * @param   array|\ArrayAccess  $data
     * @throws  InvalidArgumentException    If the data is not of the proper type.
     * @return  bool
     */
    protected function validateData($data)
    {
        if (false === is_array($data) && !$data instanceof \ArrayAccess) {
            throw new InvalidArgumentException('Data must be accessible as an array.');
        }
        return true;
    }

    /**
     * Validates that an related or child entity type is compatible with a parent or owning type.
     * Ensures that collections and relationships only contain types that have been defined.
     *
     * @param   string  $parentType
     * @param   string  $childType
     * @throws  InvalidArgumentException    The child type is not a descendant of a polymorphic parent, or the types are not identical.
     * @return  bool
     */
    protected function validateResourceTypes($parentType, $childType)
    {
        return $this->mf->validateResourceTypes($parentType, $childType);
    }

    /**
     * Creates a new attribute document.
     *
     * @param   string  $key
     * @param   mixed   $value
     * @return  Attribute
     */
    protected function createAttribute($key, $value)
    {
        return new Attribute($key, $value);
    }


}
