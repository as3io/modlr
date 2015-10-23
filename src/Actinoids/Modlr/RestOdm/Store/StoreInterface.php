<?php

namespace Actinoids\Modlr\RestOdm\Store;

use Actinoids\Modlr\RestOdm\Rest;
use Actinoids\Modlr\RestOdm\Struct;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;

/**
 * Interface for handling database operations
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface StoreInterface
{
    /**
     * Finds a single entity by id.
     *
     * @param   EntityMetadata  $metadata
     * @param   string          $identifier
     * @param   array           $fields
     * @param   array           $inclusions
     * @return  Struct\Resource
     */
    public function findRecord(EntityMetadata $metadata, $identifier, array $fields = [], array $inclusions = []);

    /**
     * Finds multiple entities by type.
     *
     * @param   EntityMetadata  $metadata
     * @param   array           $identifiers
     * @param   array           $pagination
     * @param   array           $fields
     * @param   array           $inclusions
     * @param   array           $sort
     * @return  Struct\Resource
     */
    public function findMany(EntityMetadata $metadata, array $identifiers = [], array $pagination = [], array $fields = [], array $inclusions = [], array $sort = []);

    /**
     * Creates a new record.
     *
     * @param   EntityMetadata  $metadata
     * @param   Struct\Resource $resource
     * @param   array           $fields
     * @param   array           $inclusions
     * @return  Struct\Resource
     */
    public function createRecord(EntityMetadata $metadata, Struct\Resource $resource, array $fields = [], array $inclusions = []);
}
