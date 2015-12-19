<?php

namespace Actinoids\Modlr\RestOdm\Persister\MongoDb;

use Actinoids\Modlr\RestOdm\Metadata\Interfaces\MergeableInterface;
use Actinoids\Modlr\RestOdm\Metadata\Interfaces\StorageLayerInterface;

/**
 * Defines the MongoDB storage metadata for an entity (e.g. a database object).
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class StorageMetadata implements StorageLayerInterface
{
    /**
     * The storage layer key.
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
    public function getKey()
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
