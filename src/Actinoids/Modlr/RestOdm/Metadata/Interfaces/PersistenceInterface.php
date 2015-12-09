<?php

namespace Actinoids\Modlr\RestOdm\Metadata\Interfaces;

/**
 * Defines the persistence metadata for an entity (e.g. a database object).
 * Should be loaded using the MetadataFactory, not instantiated directly.
 * Contains information about the database schema, such as db/collection/table names, indexes, etc.
 * Each implementing class must define it's own merging criteria, and handle it's own properties/methods.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface PersistenceInterface extends MergeableInterface
{
    /**
     * Returns the persister key for this persistence metadata.
     *
     * @return  string
     */
    public function getPersisterKey();
}
