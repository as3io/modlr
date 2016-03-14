<?php

namespace As3\Modlr\Metadata\Driver;

use As3\Modlr\Exception\MetadataException;
use As3\Modlr\Metadata\EntityMetadata;
use As3\Modlr\Metadata\MixinMetadata;
use As3\Modlr\StorageLayerManager;

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
     * @var EntityMetadata[]|MixinMetadata[]
     */
    private $arrayCache = [
        'model' => [],
        'mixin' => [],
    ];

    /**
     * Array cache of all entity types.
     *
     * @var array
     */
    private $allEntityTypes;

    /**
     * The Storage Layer Manager service.
     * Used to determine the Persistence and Search Metadata to use for the model.
     *
     * @var StorageLayerManager
     */
    private $storageManager;

    /**
     * Constructor.
     *
     * @param   FileLocatorInterface    $fileLocator
     * @param   Validator               $validator
     * @param   StorageLayerManager     $storageManager
     */
    public function __construct(FileLocatorInterface $fileLocator, StorageLayerManager $storageManager)
    {
        $this->fileLocator = $fileLocator;
        $this->storageManager = $storageManager;
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForType($modelType)
    {
        return $this->doLoadMetadata('model', $modelType);
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForMixin($mixinName)
    {
        return $this->doLoadMetadata('mixin', $mixinName);
    }

    /**
     * Loads the metadata instance for a model or a mixin.
     *
     * @param   string  $metaType   The metadata type, either model or mixin.
     * @param   string  $key        The metadat key name, either a model type or a mixin name.
     * @return  EntityMetadata|MixinMetadata|null
     */
    protected function doLoadMetadata($metaType, $key)
    {
        if (isset($this->arrayCache[$metaType][$key])) {
            return $this->arrayCache[$metaType][$key];
        }
        $path = $this->getFilePath($metaType, $key);

        if (null === $path) {
            return null;
        }

        $loadMethod = ('mixin' === $metaType) ? 'loadMixinFromFile' : 'loadMetadataFromFile';
        $metadata = $this->$loadMethod($key, $path);
        $this->arrayCache[$metaType][$key] = $metadata;
        return $this->arrayCache[$metaType][$key];
    }

    /**
     * Returns the file path for an entity type or mixin name.
     *
     * @param   string  $metaType   The type of metadata, either model or mixin.
     * @param   string  $key        The file key name, either the model type or the mixin name.
     * @return  string
     * @throws  MetadataException
     */
    protected function getFilePath($metaType, $key)
    {
        $method = ('mixin' === $metaType) ? 'findFileForMixin' : 'findFileForType';
        $path = $this->fileLocator->$method($key, $this->getExtension());
        if (null === $path) {
            throw MetadataException::fatalDriverError($key, sprintf('No mapping file was found.', $path));
        }
        return $path;
    }

    /**
     * {@inheritDoc}
     */
    public function getPersistenceMetadataFactory($key)
    {
        return $this->storageManager->getPersister($key)->getPersistenceMetadataFactory();
    }

    /**
     * {@inheritDoc}
     */
    public function getSearchMetadataFactory($key)
    {
        return $this->storageManager->getSearchClient($key)->getSearchMetadataFactory();
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
     * Reads the content of the file and loads it as a MixinMetadata instance.
     *
     * @param string    $mixinName
     * @param string    $path
     *
     * @return  MixinMetadata|null
     */
    abstract protected function loadMixinFromFile($mixinName, $path);

    /**
     * Returns the extension of the file.
     *
     * @return string
     */
    abstract protected function getExtension();
}
