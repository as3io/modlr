<?php

namespace Actinoids\Modlr\RestOdm\Metadata\Driver;

use Actinoids\Modlr\RestOdm\Exception\MetadataException;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Metadata\MixinMetadata;
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
