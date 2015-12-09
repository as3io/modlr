<?php

namespace Actinoids\Modlr\RestOdm\Metadata\Interfaces;

use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;

/**
 * Creates Persistence Metadata instances in the implementing format.
 * Is used by the metadata driver and/or factory for creating new instances and validation.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface PersistenceMetadataFactoryInterface
{
    /**
     * Gets a new and empty Persistence Metadata object.
     *
     * @return  PersistenceInterface
     */
    public function getNewInstance();

    /**
     * Handles additional metadata operations on the Factory load.
     *
     * @param   EntityMetadata
     */
    public function handleLoad(EntityMetadata $metadata);

    /**
     * Handles additional validation specific to this persister.
     *
     * @param   EntityMetadata
     * @throws  \Actinoids\Modlr\RestOdm\Exception\MetadataException On invalid metadata.
     */
    public function handleValidate(EntityMetadata $metadata);
}
