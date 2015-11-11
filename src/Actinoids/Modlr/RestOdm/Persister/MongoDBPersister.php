<?php

namespace Actinoids\Modlr\RestOdm\Persister;

use Actinoids\Modlr\RestOdm\Store\Store;
use Actinoids\Modlr\RestOdm\Models\Model;
use Actinoids\Modlr\RestOdm\Models\Collection;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Metadata\AttributeMetadata;
use Actinoids\Modlr\RestOdm\Metadata\RelationshipMetadata;
use Doctrine\MongoDB\Connection;
use \MongoId;

/**
 * Persists and retrieves models to/from a MongoDB database.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class MongoDBPersister implements PersisterInterface
{
    const IDENTIFIER_KEY    = '_id';
    const POLYMORPHIC_KEY   = '_type';

    /**
     * The Doctine MongoDB connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * Constructor.
     *
     * @param   Connection          $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritDoc}
     * @todo    Implement sorting and pagination (limit/skip).
     */
    public function all(EntityMetadata $metadata, Store $store, array $identifiers = [])
    {
        $criteria = $this->getRetrieveCritiera($metadata, $identifiers);
        $cursor = $this->findFromDatabase($metadata, $criteria);
        return $this->hydrateRecords($metadata, $cursor->toArray(), $store);
    }

    /**
     * {@inheritDoc}
     */
    public function inverse(EntityMetadata $owner, EntityMetadata $rel, Store $store, array $identifiers, $inverseField)
    {
        $criteria = $this->getInverseCriteria($owner, $rel, $identifiers, $inverseField);
        $cursor = $this->findFromDatabase($rel, $criteria);
        return $this->hydrateRecords($rel, $cursor->toArray(), $store);
    }

    /**
     * {@inheritDoc}
     */
    public function retrieve(EntityMetadata $metadata, $identifier, Store $store)
    {
        $criteria = $this->getRetrieveCritiera($metadata, $identifier);
        $result = $this->findFromDatabase($metadata, $criteria)->getSingleResult();
        if (null === $result) {
            return;
        }
        return $this->hydrateRecord($metadata, $result, $store);
    }

    /**
     * {@inheritDoc}
     * @todo    Optimize the changeset to query generation.
     */
    public function create(Model $model)
    {
        $metadata = $model->getMetadata();
        $insert[$this->getIdentifierKey()] = $this->convertId($model->getId());
        if (true === $metadata->isChildEntity()) {
            $insert[$this->getPolymorphicKey()] = $metadata->type;
        }

        $changeset = $model->getChangeSet();
        foreach ($changeset['attributes'] as $key => $values) {
            $value = $this->prepareAttribute($metadata->getAttribute($key), $values['new']);
            if (null === $value) {
                continue;
            }
            $insert[$key] = $value;
        }
        foreach ($changeset['hasOne'] as $key => $values) {
            $value = $this->prepareHasOne($metadata->getRelationship($key), $values['new']);
            if (null === $value) {
                continue;
            }
            $insert[$key] = $value;
        }
        foreach ($changeset['hasMany'] as $key => $values) {
            $value = $this->prepareHasMany($metadata->getRelationship($key), $values['new']);
            if (null === $value) {
                continue;
            }
            $insert[$key] = $value;
        }
        $this->createQueryBuilder($metadata)
            ->insert()
            ->setNewObj($insert)
            ->getQuery()
            ->execute()
        ;
        return $model;
    }

    /**
     * Prepares and formats an attribute value for proper insertion into the database.
     *
     * @param   AttributeMetadata   $attrMeta
     * @param   mixed               $value
     * @return  mixed
     */
    protected function prepareAttribute(AttributeMetadata $attrMeta, $value)
    {
        // Handle data type conversion, if needed.
        if ('date' === $attrMeta->dataType) {
            return new \MongoDate($value->getTimestamp(), $value->format('u'));
        }
        return $value;
    }

    /**
     * Prepares and formats a has-one relationship model for proper insertion into the database.
     *
     * @param   RelationshipMetadata    $relMeta
     * @param   Model|null              $model
     * @return  mixed
     */
    protected function prepareHasOne(RelationshipMetadata $relMeta, Model $model = null)
    {
        if (null === $model || true === $relMeta->isInverse) {
            return null;
        }
        return $this->createReference($relMeta, $model);
    }

    /**
     * Prepares and formats a has-many relationship model set for proper insertion into the database.
     *
     * @param   RelationshipMetadata    $relMeta
     * @param   Model[]|null            $models
     * @return  mixed
     */
    protected function prepareHasMany(RelationshipMetadata $relMeta, array $models = null)
    {
        if (null === $models || true === $relMeta->isInverse) {
            return null;
        }
        $references = [];
        foreach ($models as $model) {
            $references[] = $this->createReference($relMeta, $model);
        }
        return empty($references) ? null : $references;
    }

    /**
     * Creates a reference for storage of a related model in the database
     *
     * @param   RelationshipMetadata    $relMeta
     * @param   Model                   $model
     * @return  mixed
     */
    protected function createReference(RelationshipMetadata $relMeta, Model $model)
    {
        if (true === $relMeta->isPolymorphic()) {
            $reference[$this->getIdentifierKey()] = $this->convertId($model->getId());
            $reference[$this->getPolymorphicKey()] = $model->getType();
            return $reference;
        }
        return $this->convertId($model->getId());
    }

    /**
     * {@inheritDoc}
     * @todo    Optimize the changeset to query generation.
     */
    public function update(Model $model)
    {
        $metadata = $model->getMetadata();
        $criteria = $this->getRetrieveCritiera($metadata, $model->getId());
        $changeset = $model->getChangeSet();

        $update = [];
        foreach ($changeset['attributes'] as $key => $values) {
            if (null === $values['new']) {
                $op = '$unset';
                $value = 1;
            } else {
                $op = '$set';
                $value = $this->prepareAttribute($metadata->getAttribute($key), $values['new']);
            }
            $update[$op][$key] = $value;
        }

        // @todo Must prevent inverse relationships from persisting
        foreach ($changeset['hasOne'] as $key => $values) {
            if (null === $values['new']) {
                $op = '$unset';
                $value = 1;
            } else {
                $op = '$set';
                $value = $this->prepareHasOne($metadata->getRelationship($key), $values['new']);
            }
            $update[$op][$key] = $value;
        }

        foreach ($changeset['hasMany'] as $key => $values) {
            if (null === $values['new']) {
                $op = '$unset';
                $value = 1;
            } else {
                $op = '$set';
                $value = $this->prepareHasMany($metadata->getRelationship($key), $values['new']);
            }
            $update[$op][$key] = $value;
        }

        if (empty($update)) {
            return $model;
        }

        $this->createQueryBuilder($metadata)
            ->update()
            ->setQueryArray($criteria)
            ->setNewObj($update)
            ->getQuery()
            ->execute();
        ;
        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Model $model)
    {
        $metadata = $model->getMetadata();
        $criteria = $this->getRetrieveCritiera($metadata, $model->getId());

        $this->createQueryBuilder($metadata)
            ->remove()
            ->setQueryArray($criteria)
            ->getQuery()
            ->execute();
        ;
        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function generateId($strategy = null)
    {
        if (false === $this->isIdStrategySupported($strategy)) {
            throw PersisterException::nyi('ID generation currently only supports an object strategy, or none at all.');
        }
        return new MongoId();
    }

    /**
     * {@inheritDoc}
     */
    public function convertId($identifier, $strategy = null)
    {
        if (false === $this->isIdStrategySupported($strategy)) {
            throw PersisterException::nyi('ID conversion currently only supports an object strategy, or none at all.');
        }
        if ($identifier instanceof MongoId) {
            return $identifier;
        }
        return new MongoId($identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierKey()
    {
        return self::IDENTIFIER_KEY;
    }

    /**
     * {@inheritDoc}
     */
    public function getPolymorphicKey()
    {
        return self::POLYMORPHIC_KEY;
    }

    /**
     * {@inheritDoc}
     */
    public function extractType(EntityMetadata $metadata, array $data)
    {
        if (false === $metadata->isPolymorphic()) {
            return $metadata->type;
        }
        if (!isset($data[$this->getPolymorphicKey()])) {
            throw PersisterException::badRequest(sprintf('Unable to extract polymorphic type. The "%s" key was not found.', $this->getPolymorphicKey()));
        }
        return $data[$this->getPolymorphicKey()];
    }

    /**
     * Finds records from the database based on the provided metadata and criteria.
     *
     * @param   EntityMetadata  $metadata   The model metadata that the database should query against.
     * @param   array           $criteria   The query criteria.
     * @return  \Doctrine\MongoDB\Cursor
     */
    protected function findFromDatabase(EntityMetadata $metadata, array $criteria)
    {
        return $this->createQueryBuilder($metadata)
            ->find()
            ->setQueryArray($criteria)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * Processes multiple, raw MongoDB results an converts them into an array of standardized Record objects.
     *
     * @param   EntityMetadata  $metadata
     * @param   array           $results
     * @param   Store           $store
     * @return  Record[]
     */
    protected function hydrateRecords(EntityMetadata $metadata, array $results, Store $store)
    {
        $records = [];
        foreach ($results as $data) {
            $records[] = $this->hydrateRecord($metadata, $data, $store);
        }
        return $records;
    }

    /**
     * Processes raw MongoDB data an converts it into a standardized Record object.
     *
     * @param   EntityMetadata  $metadata
     * @param   array           $data
     * @param   Store           $store
     * @return  Record
     */
    protected function hydrateRecord(EntityMetadata $metadata, array $data, Store $store)
    {
        $identifier = $data[$this->getIdentifierKey()];
        unset($data[$this->getIdentifierKey()]);

        $type = $this->extractType($metadata, $data);
        unset($data[$this->getPolymorphicKey()]);

        $metadata = $store->getMetadataForType($type);
        foreach ($metadata->getRelationships() as $key => $relMeta) {
            if (!isset($data[$key])) {
                continue;
            }
            if (true === $relMeta->isMany() && !is_array($data[$key])) {
                throw PersisterException::badRequest(sprintf('Relationship key "%s" is a reference many. Expected record data type of array, "%s" found on model "%s" for identifier "%s"', $key, gettype($data[$key]), $type, $identifier));
            }
            $references = $relMeta->isOne() ? [$data[$key]] : $data[$key];

            $extracted = [];
            foreach ($references as $reference) {
                $extracted[] =  $this->extractRelationship($relMeta, $reference);
            }
            $data[$key] = $relMeta->isOne() ? reset($extracted) : $extracted;
        }
        return new Record($type, $identifier, $data);
    }

    /**
     * Extracts a standard relationship array that the store expects from a raw MongoDB reference value.
     *
     * @param   RelationshipMetadata    $relMeta
     * @param   mixed                   $reference
     * @return  array
     * @throws  \RuntimeException   If the relationship could not be extracted.
     */
    protected function extractRelationship(RelationshipMetadata $relMeta, $reference)
    {
        $simple = false === $relMeta->isPolymorphic();
        $idKey = $this->getIdentifierKey();
        $typeKey = $this->getPolymorphicKey();
        if (true === $simple && is_array($reference) && isset($reference[$idKey])) {
            return [
                'id'    => $reference[$idKey],
                'type'  => $relMeta->getEntityType(),
            ];
        } elseif (true === $simple && !is_array($reference)) {
            return [
                'id'    => $reference,
                'type'  => $relMeta->getEntityType(),
            ];
        } elseif (false === $simple && is_array($reference) && isset($reference[$idKey]) && isset($reference[$typeKey])) {
            return [
                'id'    => $reference[$idKey],
                'type'  => $reference[$typeKey],
            ];
        } else {
            throw new RuntimeException('Unable to extract a reference id.');
        }
    }

    /**
     * Gets standard database retrieval criteria for an inverse relationship.
     *
     * @param   EntityMetadata  $metadata       The entity to retrieve database records for.
     * @param   string|array    $identifiers    The IDs to query.
     * @return  array
     */
    protected function getInverseCriteria(EntityMetadata $owner, EntityMetadata $related, $identifiers, $inverseField)
    {
        $criteria[$inverseField] = $this->getIdentifierCriteria($identifiers);
        if (true === $owner->isChildEntity()) {
            // The owner is owned by a polymorphic model. Must include the type with the inverse field criteria.
            $criteria[$inverseField] = [
                $this->getIdentifierKey()   => $criteria[$inverseField],
                $this->getPolymorphicKey()  => $owner->type,
            ];
        }
        if (true === $related->isChildEntity()) {
            // The relationship is owned by a polymorphic model. Must include the type in the root criteria.
            $criteria[$this->getPolymorphicKey()] = $related->type;
        }
        return $criteria;
    }

    /**
     * Gets standard database retrieval criteria for an entity and the provided identifiers.
     *
     * @param   EntityMetadata  $metadata       The entity to retrieve database records for.
     * @param   string|array    $identifiers    The IDs to query.
     * @return  array
     */
    protected function getRetrieveCritiera(EntityMetadata $metadata, $identifiers)
    {
        $idKey = $this->getIdentifierKey();
        $criteria[$idKey] = $this->getIdentifierCriteria($identifiers);
        if (empty($criteria[$idKey])) {
            unset($criteria[$idKey]);
        }
        if (true === $metadata->isChildEntity()) {
            $criteria[$this->getPolymorphicKey()] = $metadata->type;
        }
        return $criteria;
    }

    /**
     * Creates/formats the MongoDB identifier critiera based on a provided set of ids.
     *
     * @param   string|array    $identifiers
     * @return  array
     */
    protected function getIdentifierCriteria($identifiers)
    {
        $criteria = [];
        if (is_array($identifiers)) {
            $ids = [];
            foreach ($identifiers as $id) {
                $ids[] = $this->convertId($id);
            }
            if (1 === count($ids)) {
                $criteria = $ids[0];
            } elseif (!empty($ids)) {
                $criteria = ['$in' => $ids];
            }
        } else {
            $criteria = $this->convertId($identifiers);
        }
        return $criteria;
    }

    /**
     * Creates a builder object for querying MongoDB based on the provided metadata.
     *
     * @todo    The database and collection names should not exist on the root of the metadata.
     * @todo    Eventually, a persiter metadata object should be used, that's db specific, to provide this.
     * @param   EntityMetadata  $metadata
     * @return  \Doctrine\MongoDB\Query\Builder
     */
    protected function createQueryBuilder(EntityMetadata $metadata)
    {
        $collection = $this->connection->selectCollection($metadata->db, $metadata->collection);
        return $collection->createQueryBuilder();
    }

    /**
     * Determines if the current id strategy is supported.
     *
     * @param   string|null     $strategy
     * @return  bool
     */
    protected function isIdStrategySupported($strategy)
    {
        return (null === $strategy || 'object' === $strategy);
    }
}
