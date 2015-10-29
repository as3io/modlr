<?php

namespace Actinoids\Modlr\RestOdm\Persister;

use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Doctrine\MongoDB\Connection;
use \MongoId;

/**
 * Persists and retrieves models to/from a MongoDB database.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class MongoDBPersister implements PersisterInterface
{
    const IDENTIFIER_KEY = '_id';
    const POLYMORPHIC_KEY = '_type';

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

        return new Record($type, $identifier, $data);
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
        $criteria = [$this->getIdentifierKey() => null];
        if (is_array($identifiers) && !empty($identifiers)) {
            $ids = [];
            foreach ($identifiers as $id) {
                $ids[] = $this->convertId($id);
            }
            $criteria[$this->getIdentifierKey()] = ['$in' => $ids];
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
