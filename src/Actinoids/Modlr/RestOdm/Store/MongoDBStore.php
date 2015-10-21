<?php

namespace Actinoids\Modlr\RestOdm\Store;

use Actinoids\Modlr\RestOdm\Rest;
use Actinoids\Modlr\RestOdm\Struct;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;

/**
 * MongoDB database operations.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class MongoDBStore implements StoreInterface
{
    /**
     * {@inheritDoc}
     */
    public function findRecord(EntityMetadata $metadata, $identifier, array $fields = [], array $inclusions = [])
    {
        var_dump(__METHOD__);
        die();
    }

    /**
     * {@inheritDoc}
     */
    public function findMany(EntityMetadata $metadata, array $identifiers = [], array $pagination = [], array $fields = [], array $inclusions = [], array $sort = [])
    {
        var_dump(__METHOD__);
        die();
    }
}
