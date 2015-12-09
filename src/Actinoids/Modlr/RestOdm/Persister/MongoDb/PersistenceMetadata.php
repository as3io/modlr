<?php

namespace Actinoids\Modlr\RestOdm\Persister\MongoDb;

use Actinoids\Modlr\RestOdm\Metadata\Interfaces\MergeableInterface;
use Actinoids\Modlr\RestOdm\Metadata\Interfaces\PersistenceInterface;

/**
 * Defines the MongoDB persistence metadata for an entity (e.g. a database object).
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class PersistenceMetadata implements PersistenceInterface
{
    /**
     * The persister key.
     *
     * @var string
     */
    public $persisterKey;

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
     * {@inheritDoc}
     */
    public function getPersisterKey()
    {
        return $this->persisterKey;
    }

    /**
     * {@inheritDoc}
     */
    public function merge(MergeableInterface $metadata)
    {
        return $this;
    }
}
