<?php

namespace Actinoids\Modlr\RestOdm\Store;

use Actinoids\Modlr\RestOdm\Struct;
use Actinoids\Modlr\RestOdm\Hydrator\MongoDBHydrator;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Exception\RuntimeException;
use Doctrine\MongoDB\Connection;

/**
 * MongoDB database operations.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class MongoDBStore implements StoreInterface
{
    /**
     * The Doctine MongoDB connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * Hydrator
     *
     * @var MongoDBHydrator
     */
    private $hydrator;

    /**
     * Constructor.
     *
     * @param   Connection          $connection
     * @param   MongoDBHydrator     $hydrator
     */
    public function __construct(Connection $connection, MongoDBHydrator $hydrator)
    {
        $this->connection = $connection;
        $this->hydrator = $hydrator;
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
        $resource = $this->hydrator->hydrateOne($metadata, $identifier, $result);
        $this->setIncludedData($resource, $inclusions);
        return $resource;
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
        $resource = $this->hydrator->hydrateMany($metadata, $cursor->toArray());
        $this->setIncludedData($resource, $inclusions);
        return $resource;
    }

    /**
     * {@inheritDoc}
     */
    public function createRecord(EntityMetadata $metadata, Struct\Resource $resource, array $fields = [], array $inclusions = [])
    {
        if (false === $resource->isOne()) {
            throw StoreException::badRequest('Record creation only supports single resources, not multiple');
        }
        $identifier = $this->generateIdentifier($metadata);
        $entity = $resource->getPrimaryData();
        $entity->setId($identifier);

        $record = [
            '_id'   => $identifier,
        ];

        if (true === $metadata->isChildEntity()) {
            $record['_type'] = $entity->getType();
        }

        if (true === $metadata->isPolymorphic()) {
            if (true === $metadata->isAbstract()) {
                throw StoreException::badRequest('Records cannot be persisted that are polymorphic and abstract.');
            }
            $record['_type'] = $entity->getType();
        }

        foreach ($metadata->getAttributes() as $key => $attrMeta) {
            if (false === $entity->hasAttribute($key)) {
                continue;
            }
            $attribute = $entity->getAttribute($key);
            if (null === $attribute->getValue()) {
                // Do not store null values.
                continue;
            }
            switch ($attrMeta->dataType) {
                case 'integer':
                    $value = new \MongoInt64($attribute->getValue());
                    break;
                case 'date':
                    $value = new \MongoDate($attribute->getValue()->getTimestamp());
                default:
                    $value = $attribute->getValue();
                    break;
            }
            $record[$key] = $value;
        }

        $collection = $this->connection->selectCollection($metadata->db, $metadata->collection);
        $qb = $collection->createQueryBuilder()
            ->insert()
            ->setNewObj($record)
        ;
        $qb->getQuery()->execute();
        return $resource;
    }

    /**
     * Marks an entity type and identifier for inclusion.
     *
     * @param   string  $type
     * @param   mixed   $identifier
     */
    protected function setIncludedData(Struct\Resource $resource, array $inclusions)
    {
        if (empty($inclusions)) {
            // No inclusions specified.
            return $resource;
        }
        $filter = [];
        if (!isset($inclusions['*'])) {
            $filter = $inclusions;
        }
        $toInclude = $resource->getDataToInclude($filter);
        $queried = [];
        foreach ($toInclude as $type => $identifiers) {
            // @todo Long term metadata objects should be stored directly on the Struct/Entity objects themselves.
            // @todo This would prevent needing the MF service as a dependancy in so many classes.
            $metadata = $this->hydrator->getMetadataFactory()->getMetadataForType($type);
            $formattedIds = $this->formatIdentifiers($metadata, array_keys($identifiers));
            $queried[$type] = $this->queryMongoDb($metadata, ['id' => ['$in' => $formattedIds]])->toArray();
        }
        $collection = $this->hydrator->hydrateIncluded($queried);
        $resource->setIncludedData($collection);;
        return $resource;
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
     * Generates a new identifier.
     *
     * @param   EntityMetadata  $metadata
     * @return  mixed
     */
    protected function generateIdentifier(EntityMetadata $metadata)
    {
        if ('object' !== $metadata->idStrategy) {
            throw StoreException::nyi($metadata->type);
        }
        return new \MongoId();
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
