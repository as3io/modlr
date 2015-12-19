<?php

namespace Actinoids\Modlr\RestOdm\Search\Elastic;

use Actinoids\Modlr\RestOdm\Util\EntityUtility;
use Actinoids\Modlr\RestOdm\Exception\MetadataException;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Metadata\Interfaces\StorageMetadataFactoryInterface;

/**
 * Creates Elastic search storage Metadata instances for use with metadata drivers.
 * Is also responsible for validating storage objects.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
final class StorageMetadataFactory implements StorageMetadataFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getNewInstance()
    {
        return new StorageMetadata();
    }

    /**
     * {@inheritDoc}
     */
    public function handleLoad(EntityMetadata $metadata)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function handleValidate(EntityMetadata $metadata)
    {
        $search = $metadata->search;
    }
}
