<?php

namespace Actinoids\Modlr\RestOdm\Metadata\Interfaces;

/**
 * Defines the storage layer metadata for an entity (e.g. a database or search object).
 * Should be loaded using the MetadataFactory, not instantiated directly.
 * Contains information about the database schema, such as db/collection/table names, indexes, etc.
 * Each implementing class must define it's own merging criteria, and handle it's own properties/methods.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface StorageLayerInterface extends MergeableInterface
{
    /**
     * Returns the unique key for this storage layer metadata.
     *
     * @return  string
     */
    public function getKey();
}
