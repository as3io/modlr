<?php

namespace Actinoids\Modlr\RestOdm\Hydrator;

use Actinoids\Modlr\RestOdm\Rest;
use Actinoids\Modlr\RestOdm\Struct;
use Actinoids\Modlr\RestOdm\Metadata\MetadataFactory;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Metadata\RelationshipMetadata;
use Actinoids\Modlr\RestOdm\Exception\RuntimeException;

abstract class AbstractHydrator implements HydratorInterface
{
    /**
     * @var MetadataFactory.
     */
    private $mf;

    /**
     * The resource structure factory.
     *
     * @var Struct\StructFactory
     */
    private $sf;

    /**
     * Constructor.
     *
     * @param   Connection              $connection
     * @param   Struct\StructFactory    $sf
     */
    public function __construct(MetadataFactory $mf, Struct\StructFactory $sf)
    {
        $this->mf = $mf;
        $this->sf = $sf;
    }

    /**
     * Gets the id field key expected on the flattened, array data.
     *
     * @return  string
     */
    abstract public function getIdKey();

    /**
     * Gets the polymorphic field key expected on the flattened, array data.
     *
     * @return  string
     */
    abstract public function getPolymorphicKey();

    /**
     * Gets the metadata factory.
     *
     * @return  MetadataFactory
     */
    public function getMetadataFactory()
    {
        return $this->mf;
    }

    /**
     *{@inheritDoc}
     */
    public function hydrateOne(EntityMetadata $metadata, $identifier, array $data)
    {
        $resource = $this->sf->createResource($metadata->type, 'one');
        $entity = $this->hydrateEntity($metadata, $identifier, $data);
        $this->sf->applyEntity($resource, $entity);
        return $resource;
    }

    /**
     * {@inheritDoc}
     */
    public function hydrateMany(EntityMetadata $metadata, array $items)
    {
        $resource = $this->sf->createResource($metadata->type, 'many');
        foreach ($items as $identifier => $data) {
            $entity = $this->hydrateEntity($metadata, $identifier, $data);
            $this->sf->applyEntity($resource, $entity);
        }
        return $resource;
    }

    /**
     * {@inheritDoc}
     */
    public function hydrateEntity(EntityMetadata $metadata, $identifier, array $data)
    {
        $metadata = $this->extractPolymorphicMetadata($metadata, $data);

        $entity = $this->sf->createEntity($metadata->type, $identifier);
        $this->sf->applyAttributes($entity, $data);

        foreach ($metadata->getRelationships() as $key => $relMeta) {

            if (false === array_key_exists($key, $data) || ($relMeta->isMany() && !is_array($data[$key]))) {
                continue;
            }

            $references = $relMeta->isOne() ? [$data[$key]] : $data[$key];
            $relationship = $this->sf->createRelationship($entity, $key);

            foreach ($references as $reference) {
                list($referenceId, $referenceType) = $this->extractRelationship($relMeta, $reference);
                $this->sf->applyRelationship($entity, $relationship, new Struct\Identifier($referenceId, $referenceType));
            }
        }
        return $entity;
    }

    /**
     * Hydrates included (side-loaded) data into a Struct\Collection of Struct\Entity objects.
     * Expects the data to be keyed by entity type, following by data records.
     *
     * @param   array       $dataToInclude
     * @return  Struct\Collection
     */
    public function hydrateIncluded(array $dataToInclude)
    {
        $collection = $this->sf->createCollection();
        foreach ($dataToInclude as $type => $records) {
            $metadata = $this->mf->getMetadataForType($type);
            foreach ($records as $data) {
                $identifier = $data[$this->getIdKey()];
                $entity = $this->hydrateEntity($metadata, $identifier, $data);
                $collection->add($entity);
            }
        }
        return $collection;
    }

    /**
     * Extracts an entity type and identifier from a relationship.
     *
     * @param   RelationshipMetadata    $relMeta
     * @param   mixed                   $reference
     * @return  array
     * @throws  RuntimeException        If the relationship was unable to be extracted.
     */
    protected function extractRelationship(RelationshipMetadata $relMeta, $reference)
    {
        $relEntityMeta = $this->mf->getMetadataForType($relMeta->getEntityType());
        $simple = false === $relEntityMeta->isPolymorphic();

        $idKey = $this->getIdKey();
        if (true === $simple && is_array($reference) && isset($reference[$idKey])) {
            $referenceId = $reference[$idKey];
        } elseif (true === $simple && !is_array($reference)) {
            $referenceId = $reference;
        } elseif (false === $simple && is_array($reference) && isset($reference[$idKey])) {
            $referenceId = $reference[$idKey];
        } else {
            throw new RuntimeException('Unable to extract a reference id.');
        }
        $extracted = $this->extractPolymorphicMetadata($relEntityMeta, $reference);
        return [$referenceId, $extracted->type];
    }

    /**
     * Extracts the proper, polymorphic metadata, based on the incoming array data.
     * If the entity is not polymorphic, the passed metadata is returned.
     *
     * @param   EntityMetadata  $metadata
     * @param   mixed           $data
     * @return  EntityMetadata
     * @throws  RuntimeException
     */
    protected function extractPolymorphicMetadata(EntityMetadata $metadata, $data)
    {
        if (false === $metadata->isPolymorphic()) {
            return $metadata;
        }
        if (!is_array($data)) {
            throw new RuntimeException('Unable to extract polymorphic type');
        }

        $key = $this->getPolymorphicKey();
        if (!isset($data[$key])) {
            throw new RuntimeException('A polymorphic type must be present on abstract polymorphic models.');
        }
        $type = $data[$key];
        return $this->mf->getMetadataForType($type);
    }
}
