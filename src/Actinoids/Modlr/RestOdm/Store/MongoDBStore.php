<?php

namespace Actinoids\Modlr\RestOdm\Store;

use Actinoids\Modlr\RestOdm\Rest;
use Actinoids\Modlr\RestOdm\Struct;
use Actinoids\Modlr\RestOdm\Metadata\MetadataFactory;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Metadata\RelationshipMetadata;
use Actinoids\Modlr\RestOdm\Exception\RuntimeException;
use Doctrine\MongoDB\Connection;

/**
 * MongoDB database operations.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class MongoDBStore implements StoreInterface
{
    const POLYMORPHIC_KEY = '_type';

    /**
     * The Doctine MongoDB connection.
     *
     * @var Connection
     */
    private $connection;

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
     * Entities and identifiers marked for inclusion.
     *
     * @var array
     */
    private $included = [];

    /**
     * Constructor.
     *
     * @param   Connection              $connection
     * @param   Struct\StructFactory    $sf
     */
    public function __construct(Connection $connection, MetadataFactory $mf, Struct\StructFactory $sf)
    {
        $this->connection = $connection;
        $this->mf = $mf;
        $this->sf = $sf;
    }

    /**
     * {@inheritDoc}
     */
    public function findRecord(EntityMetadata $metadata, $identifier, array $fields = [], array $inclusions = [])
    {
        $result = $this->queryMongoDb($metadata, ['id' => $this->formatIdentifiers($metadata, $identifier)], $fields)->getSingleResult();
        if (null === $result) {
            throw StoreException::recordNotFound($metadata->type, $identifier);
        }
        return $this->hydrateOne($metadata, $identifier, $result, $inclusions);
    }

    /**
     * {@inheritDoc}
     */
    public function findMany(EntityMetadata $metadata, array $identifiers = [], array $pagination = [], array $fields = [], array $inclusions = [], array $sort = [])
    {
        $criteria = [];
        if (!empty($identifiers)) {
            $criteria['id'] = ['$in' => $this->formatIdentifiers($metadata, $identifiers)];
        }
        $cursor = $this->queryMongoDb($metadata, [], $fields, $sort)->limit($pagination['limit'])->skip($pagination['offset']);
        return $this->hydrateMany($metadata, $cursor->toArray(), $inclusions);
    }

    /**
     * Hydrates a single MongoDB array record into a Struct\Resource object.
     *
     * @param   EntityMetadata  $metadata
     * @param   string          $identifier
     * @param   array           $data
     * @param   array           $inclusions
     * @return  Struct\Resource
     */
    protected function hydrateOne(EntityMetadata $metadata, $identifier, array $data, array $inclusions)
    {
        $resource = $this->sf->createResource($metadata->type, 'one');
        $entity = $this->hydrateEntity($metadata, $identifier, $data, $inclusions);
        $this->sf->applyEntity($resource, $entity);
        $resource->setIncludedData($this->hydrateIncluded());
        return $resource;
    }

    /**
     * Hydrates multiple MongoDB array records into a Struct\Resource object.
     *
     * @param   EntityMetadata  $metadata
     * @param   array           $items
     * @param   array           $data
     * @param   array           $inclusions
     * @return  Struct\Resource
     */
    protected function hydrateMany(EntityMetadata $metadata, array $items, array $inclusions)
    {
        $resource = $this->sf->createResource($metadata->type, 'many');
        foreach ($items as $identifier => $data) {
            $entity = $this->hydrateEntity($metadata, $identifier, $data, $inclusions);
            $this->sf->applyEntity($resource, $entity);
        }
        $resource->setIncludedData($this->hydrateIncluded());
        return $resource;
    }

    /**
     * Hydrates a single MongoDB record into a Struct\Entity object.
     *
     * @param   EntityMetadata  $metadata
     * @param   string          $identifier
     * @param   array           $data
     * @param   array           $inclusions
     * @return  Struct\Entity
     */
    protected function hydrateEntity(EntityMetadata $metadata, $identifier, array $data, array $inclusions)
    {
        $metadata = $this->extractPolymorphicMetadata($metadata, $data);

        // @todo This shouldn't run here: findMany will hit this method 50 times!
        // @todo However, the polymorphism must be set based on the incoming data for each record, otherwise the includes will not work
        // @todo Test how much of a performance hit this is
        $inclusions = $this->getInclusions($metadata, $inclusions);

        $entity = $this->sf->createEntity($metadata->type, $identifier);
        $this->sf->applyAttributes($entity, $data);

        foreach ($metadata->getRelationships() as $key => $relMeta) {

            if (!isset($data[$key]) || ($relMeta->isMany() && !is_array($data[$key]))) {
                continue;
            }

            $references = $relMeta->isOne() ? [$data[$key]] : $data[$key];
            $relationship = $this->sf->createRelationship($entity, $key);

            foreach ($references as $reference) {
                list($referenceId, $referenceType) = $this->extractReference($relMeta, $reference);
                if (false === $relMeta->isInverse && isset($inclusions[$key])) {
                    // @todo MUST HANDLE INVERSE INCLUSIONS
                    $this->markForInclusion($referenceType, $referenceId);
                }
                $this->sf->applyRelationship($entity, $relationship, new Struct\Identifier($referenceId, $referenceType));
            }
        }
        return $entity;
    }

    /**
     * Hydrates included (side-loaded) data in a Struct\Collection of Struct\Entity objects.
     *
     * @return  Struct\Collection
     */
    protected function hydrateIncluded()
    {
        $collection = $this->sf->createCollection();
        foreach ($this->included as $type => $identifiers) {
            $metadata = $this->mf->getMetadataForType($type);

            $formattedIds = $this->formatIdentifiers($metadata, array_keys($identifiers));
            $cursor = $this->queryMongoDb($metadata, ['_id' => ['$in' => $formattedIds]]);
            foreach ($cursor as $data) {
                $identifier = $data['_id'];
                $entity = $this->hydrateEntity($metadata, $identifier, $data, []);
                $collection->add($entity);
            }
        }
        $this->included = [];
        return $collection;
    }

    /**
     * Extracts an entity type and identifier from a relationship.
     *
     * @param   RelationshipMetadata    $relMeta
     * @param   mixed                   $reference
     * @param   bool                    $simple
     * @return  array
     * @throws  RuntimeException
     */
    protected function extractReference(RelationshipMetadata $relMeta, $reference)
    {
        $relEntityMeta = $this->mf->getMetadataForType($relMeta->getEntityType());
        $simple = false === $relEntityMeta->isPolymorphic();

        if (true === $simple && is_array($reference) && isset($reference['_id'])) {
            $referenceId = $reference['_id'];
        } elseif (true === $simple && !is_array($reference)) {
            $referenceId = $reference;
        } elseif (false === $simple && is_array($reference) && isset($reference['_id'])) {
            $referenceId = $reference['_id'];
        } else {
            throw new RuntimeException('Unable to extract a reference id.');
        }
        $extracted = $this->extractPolymorphicMetadata($relEntityMeta, $reference);
        return [$referenceId, $extracted->type];
    }

    /**
     * Extracts the proper, polymorphic metadata, based on the incoming MongoDB data.
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

        if (!isset($data[self::POLYMORPHIC_KEY])) {
            throw new RuntimeException('A polymorphic type must be present on abstract polymorphic models.');
        }
        $type = $data[self::POLYMORPHIC_KEY];
        return $this->mf->getMetadataForType($type);
    }

    /**
     * Marks an entity type and identifier for inclusion.
     *
     * @param   string  $type
     * @param   mixed   $identifier
     */
    protected function markForInclusion($type, $identifier)
    {
        $this->included[$type][(String) $identifier] = true;
        return $this;
    }

    /**
     * Gets the fields to include, based on defaults, and validates relationship keys.
     *
     * @param   EntityMetadata  $metadata
     * @param   array           $inclusions
     * @return  array
     * @throws  StoreException
     */
    protected function getInclusions(EntityMetadata $metadata, array $inclusions)
    {
        if (empty($inclusions)) {
            // No inclusions.
            return $inclusions;
        }
        if (isset($inclusions['*'])) {
            // Include all.
            $formatted = [];
            foreach (array_keys($metadata->getRelationships()) as $fieldKey) {
                $formatted[$fieldKey] = true;
            }
            return $formatted;
        }
        // Specified.
        foreach ($inclusions as $fieldKey => $inclusion) {
            if (false === $metadata->hasRelationship($fieldKey)) {
                throw StoreException::invalidInclude($metadata->type, $fieldKey);
            }
        }
        return $inclusions;
    }

    /**
     * Queries MongoDB via Doctrine's QueryBuilder
     *
     * @param   EntityMetadata  $metadata
     * @param   array           $criteria
     * @param   array           $fields
     * @param   array           $sort
     * @return  \Doctrine\MongoDB\Cursor
     */
    protected function queryMongoDb(EntityMetadata $metadata, array $criteria, array $fields = [], array $sort = [])
    {
        // Note: if entity metadata needs to change to not include db and collection (e.g. database agnostic) this would need to change.
        $collection = $this->connection->selectCollection($metadata->db, $metadata->collection);

        if ($metadata->isChildEntity()) {
            $criteria['_type'] = $metadata->type;
        }

        if (isset($criteria['id'])) {
            $criteria['_id'] = $criteria['id'];
            unset($criteria['id']);
        }

        if (isset($sort['id'])) {
            $sort['_id'] = $sort['id'];
            unset($sort['id']);
        }

        $qb = $collection->createQueryBuilder()
            ->find()
            ->setQueryArray($criteria)
        ;
        $qb->select($fields);
        if (!empty($sort)) {
            $qb->sort($sort);
        }
        return $qb->getQuery()->execute();
    }

    /**
     * Formats the identifier to the proper data type.
     *
     * @todo    This may need to changed to a more global handler??
     * @param   EntityMetadata  $metadata
     * @param   mixed           $identifer
     * @return  mixed
     */
    protected function formatIdentifiers(EntityMetadata $metadata, $identifier)
    {
        if ('object' !== $metadata->idStrategy) {
            throw StoreException::nyi($metadata->type);
        }
        $toFormat = (is_array($identifier)) ? $identifier : [$identifier];

        $formatted = [];
        foreach ($toFormat as $id) {
            $formatted[] = ($id instanceof \MongoId) ? $id : new \MongoId($id);
        }
        return (is_array($identifier)) ? $formatted : $formatted[0];
    }
}
