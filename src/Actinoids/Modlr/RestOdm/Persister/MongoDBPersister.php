<?php

namespace Actinoids\Modlr\RestOdm\Persister;

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
    public function all(EntityMetadata $metadata, array $identifiers = [])
    {
        $criteria = $this->getRetrieveCritiera($metadata, $identifiers);
        $cursor = $this->createQueryBuilder($metadata)
            ->find()
            ->setQueryArray($criteria)
            ->getQuery()
            ->execute()
        ;
        $records = [];
        foreach ($cursor as $result) {
            $records[] = $this->hydrateRecord($metadata, $result);
        }
        return $records;
    }

    /**
     * {@inheritDoc}
     */
    public function retrieve(EntityMetadata $metadata, $identifier)
    {
        $criteria = $this->getRetrieveCritiera($metadata, $identifier);
        $result = $this->createQueryBuilder($metadata)
            ->find()
            ->setQueryArray($criteria)
            ->getQuery()
            ->execute()
            ->getSingleResult()
        ;
        if (null === $result) {
            return;
        }
        return $this->hydrateRecord($metadata, $result);
    }

    /**
     * {@inheritDoc}
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

    protected function prepareAttribute(AttributeMetadata $attrMeta, $value)
    {
        // @todo Handle conversion, if needed.
        return $value;
    }

    protected function prepareHasOne(RelationshipMetadata $relMeta, Model $model = null)
    {
        if (null === $model) {
            return null;
        }
        return $this->createReference($relMeta, $model);
    }

    protected function prepareHasMany(RelationshipMetadata $relMeta, array $models = null)
    {
        if (null === $models) {
            return null;
        }
        $references = [];
        foreach ($models as $model) {
            $references[] = $this->createReference($relMeta, $model);
        }
        return empty($references) ? null : $references;
    }

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
     */
    public function update(Model $model)
    {
        $metadata = $model->getMetadata();
        $criteria = $this->getRetrieveCritiera($metadata, $model->getId());
        $changeset = $model->getChangeSet();

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

        $this->createQueryBuilder($metadata)
            ->update()
            ->setQueryArray($criteria)
            ->setNewObj($update)
            ->getQuery()
            ->execute();
        ;
        return $model;
    }

    protected function formatRelationship(RelationshipMetadata $relMeta, $value)
    {
        if (true === $relMeta->isOne()) {
            return $this->formatReference($relMeta, $value);
        }
        $collection = [];
        foreach ($value as $model) {
            $collection[] = $this->formatReference($relMeta, $model);
        }
        return $collection;
    }

    protected function formatReference(RelationshipMetadata $relMeta, Model $model)
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
     * Processes raw MongoDB data an converts it into a standardized Record object.
     *
     * @param   EntityMetadata  $metadata
     * @param   array           $data
     * @return  Record
     */
    protected function hydrateRecord(EntityMetadata $metadata, array $data)
    {
        $identifier = $data[$this->getIdentifierKey()];
        unset($data[$this->getIdentifierKey()]);

        $type = $this->extractType($metadata, $data);
        unset($data[$this->getPolymorphicKey()]);

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
     * Gets standard database retrieval criteria for an entity and the provided identifiers.
     *
     * @param   EntityMetadata  $metadata       The entity to retrieve database records for.
     * @param   string|array    $identifiers    The IDs to query.
     * @return  array
     */
    protected function getRetrieveCritiera(EntityMetadata $metadata, $identifiers)
    {
        $criteria = [];
        if (is_array($identifiers)) {
            $ids = [];
            foreach ($identifiers as $id) {
                $ids[] = $this->convertId($id);
            }
            if (!empty($ids)) {
                $criteria[$this->getIdentifierKey()] = ['$in' => $ids];
            }
        } else {
            $criteria[$this->getIdentifierKey()] = $this->convertId($identifiers);
        }
        if (true === $metadata->isChildEntity()) {
            $criteria[$this->getPolymorphicKey()] = $metadata->type;
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
