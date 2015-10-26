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
    const ID_FIELD = '_id';
    const POLYMORPHIC_KEY = '_type';

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
        $identifier = $this->generateIdentifier($metadata);

        $entity = $resource->getPrimaryData();
        $entity->setId($identifier);

        $record = $this->extractRawRecord($metadata, $resource);
        $this->dbInsert($metadata, $record);
        $this->setIncludedData($resource, $inclusions);
        return $resource;
    }

    /**
     * {@inheritDoc}
     */
    public function updateRecord(EntityMetadata $metadata, Struct\Resource $resource, array $fields = [], array $inclusions = [])
    {
        $new = $resource->getPrimaryData();
        $identifier = $new->getId();

        $result = $this->queryMongoDb($metadata, ['id' => $this->formatIdentifiers($metadata, $identifier)], $fields)->getSingleResult();
        if (null === $result) {
            throw StoreException::recordNotFound($metadata->type, $identifier);
        }
        $old = $this->hydrator->hydrateOne($metadata, $identifier, $result)->getPrimaryData();
        $changeset = $this->calculateChangeSet($metadata, $resource, $old, $new);

        if (!empty($changeset)) {
            $this->dbUpdate($metadata, $identifier, $changeset);
            return $this->findRecord($metadata, $identifier, $fields, $inclusions);
        } else {
            $this->setIncludedData($old, $inclusions);
            return $old;
        }
    }

    protected function calculateChangeSet(EntityMetadata $metadata, Struct\Resource $resource, Struct\Entity $old, Struct\Entity $new)
    {
        if ($old->getId() !== $new->getId()) {
            throw StoreException::badRequest('The old and new ids are mismatched. Cannot update.');
        }
        if ($old->getType() !== $new->getType()) {
            throw StoreException::badRequest('The old and new entity types are mismatched. Cannot update.');
        }

        $changeset = [];
        foreach ($metadata->getAttributes() as $key => $attrMeta) {
            if (false === $new->hasAttribute($key)) {
                continue;
            }

            $oldValue = ($old->hasAttribute($key)) ? $old->getAttribute($key)->getValue() : null;
            $newValue = $new->getAttribute($key)->getValue();

            if ($oldValue != $newValue) {
                $changeset[$key][0] = $oldValue;
                $changeset[$key][1] = $newValue;
            }
        }

        $oldRel = $this->extractRelationships($metadata, $old, $resource, []);
        $newRel = $this->extractRelationships($metadata, $new, $resource, []);

        foreach ($metadata->getRelationships() as $key => $relMeta) {
            if (false === $new->hasRelationship($key)) {
                continue;
            }

            $oldValue = (isset($oldRel[$key])) ? $oldRel[$key] : null;
            $newValue = (isset($newRel[$key])) ? $newRel[$key] : null;

            if (empty($oldValue) && empty($newValue)) {
                continue;
            } elseif (empty($oldValue) && !empty($newValue)) {
                $changeset[$key][0] = null;
                $changeset[$key][1] = $newValue;
            } elseif (!empty($oldValue) && empty($newValue)) {
                $changeset[$key][0] = $oldValue;
                $changeset[$key][1] = null;
            }

            if (true === $relMeta->isOne()) {
                if ($oldValue['_id'] != $newValue['_id']) {
                    $changeset[$key][0] = $oldValue;
                    $changeset[$key][1] = $newValue;
                } else {
                    continue;
                }
            } else {
                if (count($oldValue) !== count($newValue)) {
                    $changeset[$key][0] = $oldValue;
                    $changeset[$key][1] = $newValue;
                } else {
                    $oldIds = [];
                    $newIds = [];
                    foreach ($oldValue as $item) {
                        // @todo Prevent allowing relating the same model more than once.
                        $oldIds[(String) $item['_id']] = true;
                    }
                    ksort($oldIds);

                    foreach ($newValue as $item) {
                        // @todo Prevent allowing relating the same model more than once.
                        $newIds[(String) $item['_id']] = true;
                    }
                    ksort($newIds);
                    if ($oldIds !== $newIds) {
                        $changeset[$key][0] = $oldValue;
                        $changeset[$key][0] = $newValue;
                    }
                }
            }
        }
        return $changeset;
    }

    /**
     * Extracts a raw MongoDB array record from a resource.
     *
     * @param   EntityMetadata  $metadata
     * @param   Struct\Resource $resource
     * @return  array
     */
    protected function extractRawRecord(EntityMetadata $metadata, Struct\Resource $resource)
    {
        $entity = $resource->getPrimaryData();
        $record = $this->extractIdAndType($metadata, $entity, []);
        $record = $this->extractAttributes($metadata, $entity, $record);
        $record = $this->extractRelationships($metadata, $entity, $resource, $record);
        return $record;
    }

    /**
     * Extracts a raw ID and Type and adds to the provided raw record.
     *
     * @param   EntityMetadata      $metadata
     * @param   Struct\Identifier   $identifier
     * @param   array               $record
     * @return  array
     */
    protected function extractIdAndType(EntityMetadata $metadata, Struct\Identifier $identifier, array $record)
    {
        $record['_id'] = $this->formatIdentifiers($metadata, $identifier->getId());
        $record['_type'] = $identifier->getType();
        return $record;
    }

    /**
     * Extracts raw attributes from an entity and adds them to the provided raw record.
     *
     * @param   EntityMetadata  $metadata
     * @param   Struct\Entity   $entity
     * @param   array           $record
     * @return  array
     */
    protected function extractAttributes(EntityMetadata $metadata, Struct\Entity $entity, array $record)
    {
        foreach ($metadata->getAttributes() as $key => $attrMeta) {
            if (false === $entity->hasAttribute($key)) {
                continue;
            }
            $attribute = $entity->getAttribute($key);
            if (null === $attribute->getValue()) {
                // Do not store null values.
                continue;
            }
            $record[$key] = $attribute->getValue();
        }
        return $record;
    }

    /**
     * Extracts raw relations from an entity and adds them to the provided raw record.
     *
     * @param   EntityMetadata  $metadata
     * @param   Struct\Entity   $entity
     * @param   Struct\Resource $resource
     * @param   array           $record
     * @return  array
     */
    protected function extractRelationships(EntityMetadata $metadata, Struct\Entity $entity, Struct\Resource $resource, array $record, $new = false)
    {
        $queried = $this->queryIncludedData($resource);
        foreach ($metadata->getRelationships() as $key => $relMeta) {
            if (false === $entity->hasRelationship($key)) {
                // Entity does not have the relationship.
                continue;
            }
            $relationship = $entity->getRelationship($key);
            if (false === $relationship->hasData() && false === $new) {
                continue;
            }

            $relatedEntityMetadata = $this->hydrator->getMetadataFactory()->getMetadataForType($relMeta->entityType);
            if ($relationship->isOne()) {
                $id = $relationship->getPrimaryData()->getId();
                if (empty($id)) {
                    continue;
                }
                $record[$key] = $this->extractIdAndType($relatedEntityMetadata, $relationship->getPrimaryData(), []);
            } else {
                $many = [];
                foreach ($relationship->getPrimaryData() as $identifier) {
                    $id = $identifier->getId();
                    if (empty($id)) {
                        continue;
                    }
                    $many[] = $this->extractIdAndType($relatedEntityMetadata, $identifier, []);
                }
                $record[$key] = $many;
            }
        }
        return $record;
    }

    /**
     * Inserts a raw record to the database.
     *
     * @param   EntityMetadata  $metadata
     * @param   array           $record
     */
    protected function dbInsert(EntityMetadata $metadata, array $record)
    {
        if (true === $metadata->isPolymorphic() && true === $metadata->isAbstract()) {
            throw StoreException::badRequest('Records cannot be persisted that are polymorphic and abstract.');
        }
        $qb = $this->createQueryBuilder($metadata)
            ->insert()
            ->setNewObj($record)
        ;
        return $qb->getQuery()->execute();
    }

    /**
     * Inserts a raw record to the database.
     *
     * @param   EntityMetadata  $metadata
     * @param   mixed           $identifier
     * @param   array           $changeset
     */
    protected function dbUpdate(EntityMetadata $metadata, $identifier, array $changeset)
    {
        if (true === $metadata->isPolymorphic() && true === $metadata->isAbstract()) {
            throw StoreException::badRequest('Records cannot be persisted that are polymorphic and abstract.');
        }
        $qb = $this->createQueryBuilder($metadata)
            ->update()
            ->field('_id')->equals($this->formatIdentifiers($metadata, $identifier))
        ;
        foreach ($changeset as $key => $set) {
            list($old, $new) = $set;
            if (null === $new) {
                $qb->field($key)->unsetField();
                continue;
            }
            $qb->field($key)->set($new);
        }
        return $qb->getQuery()->execute();
    }

    /**
     * Creates a query builder object based on the provided metadata.
     *
     * @param   EntityMetadata  $metadata
     * @return  \Doctrine\MongoDB\Query\Builder
     */
    protected function createQueryBuilder(EntityMetadata $metadata)
    {
        $collection = $this->connection->selectCollection($metadata->db, $metadata->collection);
        return $collection->createQueryBuilder();
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
        $queried = $this->queryIncludedData($resource, $filter);
        $collection = $this->hydrator->hydrateIncluded($queried);
        $resource->setIncludedData($collection);
        return $resource;
    }

    protected function queryIncludedData(Struct\Resource $resource, array $filter = [])
    {
        $toQuery = $resource->getDataToInclude($filter);
        $queried = [];
        foreach ($toQuery as $type => $identifiers) {
            // @todo Long term metadata objects should be stored directly on the Struct/Entity objects themselves.
            // @todo This would prevent needing the MF service as a dependancy in so many classes.
            $metadata = $this->hydrator->getMetadataFactory()->getMetadataForType($type);
            $formattedIds = $this->formatIdentifiers($metadata, array_keys($identifiers));
            $queried[$type] = $this->queryMongoDb($metadata, ['id' => ['$in' => $formattedIds]])->toArray();
        }
        return $queried;
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
        $criteria['_type'] = $metadata->type;
        if (isset($criteria['id'])) {
            $criteria['_id'] = $criteria['id'];
            unset($criteria['id']);
        }

        if (isset($sort['id'])) {
            $sort['_id'] = $sort['id'];
            unset($sort['id']);
        }

        $qb = $this->createQueryBuilder($metadata)
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
