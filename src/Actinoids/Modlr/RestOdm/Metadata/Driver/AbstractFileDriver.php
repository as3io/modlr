<?php

namespace Actinoids\Modlr\RestOdm\Metadata\Driver;

use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Exception\MetadataException;
use Actinoids\Modlr\RestOdm\Persister\PersisterManager;

/**
 * Abstract metadata file driver.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class AbstractFileDriver implements DriverInterface
{
    /**
     * The file locator for locating metadata files.
     *
     * @var FileLocatorInterface
     */
    private $fileLocator;

    /**
     * Array cache for in-memory loaded metadata objects.
     *
     * @var EntityMetadata[]
     */
    private $arrayCache = [];

    /**
     * Array cache of all entity types.
     *
     * @var array
     */
    private $allEntityTypes;

    /**
     * The Persister Manager service.
     * Used to determine the Persistence Metadata to use for the model.
     *
     * @var PersisterManager
     */
    private $persisterManager;

    /**
     * Constructor.
     *
     * @param   FileLocatorInterface    $fileLocator
     * @param   Validator               $validator
     */
    public function __construct(FileLocatorInterface $fileLocator, PersisterManager $persisterManager)
    {
        $this->fileLocator = $fileLocator;
        $this->persisterManager = $persisterManager;
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForType($type)
    {
        if (isset($this->arrayCache[$type])) {
            return $this->arrayCache[$type];
        }
        $path = $this->getFilePathForType($type);

        if (null === $path) {
            return null;
        }
        $metadata = $this->loadMetadataFromFile($type, $path);
        $this->arrayCache[$type] = $metadata;
        return $this->arrayCache[$type];
    }

    /**
     * Returns the file path for an entity type.
     *
     * @param   string  $type
     * @return  string
     * @throws  MetadataException
     */
    protected function getFilePathForType($type)
    {
        $path = $this->fileLocator->findFileForType($type, $this->getExtension());
        if (null === $path) {
            throw MetadataException::fatalDriverError($type, sprintf('No mapping file was found.', $path));
        }
        return $path;
    }

    /**
     * {@inheritDoc}
     */
    public function getPersistenceMetadataFactory($persisterKey)
    {
        return $this->persisterManager->getPersister($persisterKey)->getPersistenceMetadataFactory();
    }

    /**
     * {@inheritDoc}
     */
    public function getAllTypeNames()
    {
        if (null === $this->allEntityTypes) {
            $this->allEntityTypes = $this->fileLocator->findAllTypes($this->getExtension());
        }
        return $this->allEntityTypes;
    }

    /**
     * Reads the content of the file and loads it as an EntityMetadata instance.
     *
     * @param string    $type
     * @param string    $path
     *
     * @return  EntityMetadata|null
     */
    abstract protected function loadMetadataFromFile($type, $path);

    /**
     * Returns the extension of the file.
     *
     * @return string
     */
    abstract protected function getExtension();
}
