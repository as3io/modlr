<?php

namespace Actinoids\Modlr\RestOdm\Persister\MongoDb;

use Actinoids\Modlr\RestOdm\Util\EntityUtility;
use Actinoids\Modlr\RestOdm\Exception\MetadataException;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Metadata\Interfaces\StorageMetadataFactoryInterface;

/**
 * Creates MongoDb storage Metadata instances for use with metadata drivers.
 * Is also responsible for validating storage objects.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
final class StorageMetadataFactory implements StorageMetadataFactoryInterface
{
    /**
     * @var EntityUtility
     */
    private $entityUtil;

    /**
     * Constructor.
     *
     * @param   EntityUtility   $entityUtl
     */
    public function __construct(EntityUtility $entityUtil)
    {
        $this->entityUtil = $entityUtil;
    }

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
        if (null === $metadata->persistence->collection) {
            $metadata->persistence->collection = $metadata->type;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function handleValidate(EntityMetadata $metadata)
    {
        $persistence = $metadata->persistence;

        $validIdStrategies = ['object'];
        if (!in_array($persistence->idStrategy, $validIdStrategies)) {
            throw MetadataException::invalidMetadata($metadata->type, sprintf('The persistence id strategy "%s" is invalid. Valid types are "%s"', $persistence->idStrategy, implode('", "', $validIdStrategies)));
        }

        if (false === $metadata->isChildEntity() && (empty($persistence->db) || empty($persistence->collection))) {
            throw MetadataException::invalidMetadata($metadata->type, 'The persistence database and collection names cannot be empty.');
        }

        if (false === $this->entityUtil->isEntityTypeValid($persistence->collection)) {
            throw MetadataException::invalidMetadata(
                $metadata->type,
                sprintf('The entity persistence collection "%s" is invalid based on the configured name format "%s"',
                        $persistence->collection,
                        $this->entityUtil->getRestConfig()->getEntityFormat()
                )
            );
        }
    }
}
