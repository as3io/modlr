<?php

namespace Actinoids\Modlr\RestOdm\Hydrator;

use Actinoids\Modlr\RestOdm\Struct;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;

/**
 * Hydrator classes implementation details.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface HydratorInterface
{
    /**
     * Hydrates a single, flattened array record into a Struct\Resource object.
     *
     * @param   EntityMetadata  $metadata
     * @param   string          $identifier
     * @param   array           $data
     * @return  Struct\Resource
     */
    public function hydrateOne(EntityMetadata $metadata, $identifier, array $data);

    /**
     * Hydrates multiple, flattened array records into a Struct\Resource object.
     *
     * @param   EntityMetadata  $metadata
     * @param   array           $items
     * @param   array           $data
     * @return  Struct\Resource
     */
    public function hydrateMany(EntityMetadata $metadata, array $items);

    /**
     * Hydrates a single, flattened array record into a Struct\Entity object.
     *
     * @param   EntityMetadata  $metadata
     * @param   string          $identifier
     * @param   array           $data
     * @return  Struct\Entity
     */
    public function hydrateEntity(EntityMetadata $metadata, $identifier, array $data);
}
