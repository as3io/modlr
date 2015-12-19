<?php

namespace Actinoids\Modlr\RestOdm\Metadata\Interfaces;

use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;

/**
 * Creates Storage Layer Metadata (persistence/search) instances in the implementing format.
 * Is used by the metadata driver and/or factory for creating new instances and validation.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface StorageMetadataFactoryInterface
{
    /**
     * Gets a new and empty Storage Layer Metadata object.
     *
     * @return  StorageLayerInterface
     */
    public function getNewInstance();

    /**
     * Handles additional metadata operations on the Factory load.
     *
     * @param   EntityMetadata
     */
    public function handleLoad(EntityMetadata $metadata);

    /**
     * Handles additional validation specific to this storage layaer.
     *
     * @param   EntityMetadata
     * @throws  \Actinoids\Modlr\RestOdm\Exception\MetadataException On invalid metadata.
     */
    public function handleValidate(EntityMetadata $metadata);
}
