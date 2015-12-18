<?php

namespace Actinoids\Modlr\RestOdm\Metadata\Interfaces;

use Actinoids\Modlr\RestOdm\Metadata\AttributeMetadata;
use Actinoids\Modlr\RestOdm\Metadata\FieldMetadata;

/**
 * Defines the search metadata for an entity.
 * Should be loaded using the MetadataFactory, not instantiated directly.
 * Contains information about the search schema, such as db/collection/table names, indexes, etc.
 * Each implementing class must define it's own merging criteria, and handle it's own properties/methods.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface SearchInterface extends MergeableInterface
{
    /**
     * Returns the client key for this search metadata.
     *
     * @return  string
     */
    public function getClientKey();
}
