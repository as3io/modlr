<?php

namespace As3\Modlr\RestOdm\Metadata;

use As3\Modlr\RestOdm\Exception\MetadataException;
use As3\Modlr\RestOdm\Metadata\Driver\DriverInterface;
use As3\Modlr\RestOdm\Metadata\Cache\CacheInterface;
use As3\Modlr\RestOdm\Util\EntityUtility;
use As3\Modlr\RestOdm\Exception\InvalidArgumentException;

/**
 * The primary MetadataFactory service.
 * Returns EntityMetadata instances for supplied entity types.
 * Can also write and retrieve these instances from cache, if supplied.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class MetadataFactory implements MetadataFactoryInterface
{
    /**
     * The Metadata driver.
     *
     * @var DriverInterface
     */
    private $driver;

    /**
     * Entity utility. Used for formatting and validation.
     *
     * @var EntityUtility
     */
    private $entityUtil;

    /**
     * The Metadata cache instance.
     * Is optional, if defined using the setter.
     *
     * @var CacheInterface
     */
    private $cache;

    /**
     * Flags whether metadata caching is enabled.
     *
     * @var bool
     */
    private $cacheEnabled = true;

    /**
     * In-memory loaded Metadata instances.
     *
     * @var EntityMetadata[]
     */
    private $loaded;

    /**
     * Constructor.
     *
     * @param   DriverInterface $driver
     */
    public function __construct(DriverInterface $driver, EntityUtility $entityUtil)
    {
        $this->driver = $driver;
        $this->entityUtil = $entityUtil;
    }

    /**
     * Sets the cache instance to use for reading/writing Metadata objects.
     *
     * @param   CacheInterface  $cache
     * @return  self
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * Gets the cache instance.
     *
     * @return  CacheInterface|null
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Enables or disables the cache.
     *
     * @param   bool    $bit
     * @return  self
     */
    public function enableCache($bit = true)
    {
        $this->cacheEnabled = (Boolean) $bit;
        return $this;
    }

    /**
     * Determines if cache is enbled.
     *
     * @return  bool
     */
    public function hasCache()
    {
        return null !== $this->getCache() && true === $this->cacheEnabled;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadataForType($type)
    {
        if (null !== $metadata = $this->doLoadMetadata($type)) {
            // Found in memory or from cache implementation
            return $metadata;
        }

        // Loop through the type hierarchy (extension) and merge metadata objects.
        foreach ($this->driver->getTypeHierarchy($type) as $hierType) {

            if (null !== $loaded = $this->doLoadMetadata($hierType)) {
                // Found in memory or from cache implementation
                $this->mergeMetadata($metadata, $loaded);
                continue;
            }

            // Load from driver source
            $loaded = $this->driver->loadMetadataForType($hierType);

            if (null === $loaded) {
                throw MetadataException::mappingNotFound($type);
            }

            // Validate the metadata object.
            $this->entityUtil->validateMetadata($hierType, $loaded, $this);

            // Handle persistence specific loading and validation.
            $persisterKey = $loaded->persistence->getKey();
            $persistenceFactory = $this->driver->getPersistenceMetadataFactory($persisterKey);

            $persistenceFactory->handleLoad($loaded);
            $persistenceFactory->handleValidate($loaded);

            // Handle search specific loading and validation.
            $clientKey = $loaded->search->getKey();
            $searchFactory = $this->driver->getSearchMetadataFactory($clientKey);

            $searchFactory->handleLoad($loaded);
            $searchFactory->handleValidate($loaded);

            $this->mergeMetadata($metadata, $loaded);
            $this->doPutMetadata($loaded);
        }

        if (null === $metadata) {
            throw MetadataException::mappingNotFound($type);
        }

        $this->doPutMetadata($metadata);
        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllTypeNames()
    {
        return $this->driver->getAllTypeNames();
    }

    /**
     * {@inheritDoc}
     */
    public function getAllMetadata()
    {
        $metadatas = [];
        foreach ($this->getAllTypeNames() as $type) {
            $metadatas[] = $this->getMetadataForType($type);
        }
        return $metadatas;
    }

    /**
     * {@inheritDoc}
     */
    public function metadataExists($type)
    {
        if (null !== $metadata = $this->doLoadMetadata($type)) {
            // Found in memory or from cache implementation
            return true;
        }
        return null !== $this->driver->loadMetadataForType($type);
    }

    /**
     * Determines if a type is direct child of another type.
     *
     * @param   string  $child
     * @param   string  $parent
     * @return  bool
     */
    public function isChildOf($child, $parent)
    {
        $childMeta = $this->getMetadataForType($child);
        if (false === $childMeta->isChildEntity()) {
            return false;
        }
        return $childMeta->getParentEntityType() === $parent;
    }

    /**
     * Determines if a type is an ancestor of another type.
     *
     * @param   string  $parent
     * @param   string  $child
     * @return  bool
     */
    public function isAncestorOf($parent, $child)
    {
        return $this->isDescendantOf($child, $parent);
    }

    /**
     * Determines if a type is a descendant of another type.
     *
     * @param   string  $child
     * @param   string  $parent
     * @return  bool
     */
    public function isDescendantOf($child, $parent)
    {
        $childMeta = $this->getMetadataForType($child);
        if (false === $childMeta->isChildEntity()) {
            return false;
        }
        if ($childMeta->getParentEntityType() === $parent) {
            return true;
        }
        return $this->isDescendantOf($childMeta->getParentEntityType(), $parent);
    }

    public function validateResourceTypes($parentType, $childType)
    {
        $meta = $this->getMetadataForType($parentType);
        if (true === $meta->isPolymorphic()) {
            if (true === $meta->isAbstract() && false === $this->isDescendantOf($childType, $parentType)) {
                throw new InvalidArgumentException(sprintf('The resource type "%s" is polymorphic and abstract. Resource "%s" must be a descendent of "%s"', $parentType, $childType, $parentType));
            }
        }

        if (false === $meta->isPolymorphic() && $parentType !== $childType) {
            throw new InvalidArgumentException(sprintf('This resource only supports resources of type "%s" - resource type "%s" was provided', $parentType, $childType));
        }
        return true;
    }

    /**
     * Merges two sets of EntityMetadata.
     * Is used for applying inheritance information.
     *
     * @param   EntityMetadata  &$metadata
     * @param   EntityMetadata  $toAdd
     */
    private function mergeMetadata(EntityMetadata &$metadata = null, EntityMetadata $toAdd)
    {
        if (null === $metadata) {
            $metadata = clone $toAdd;
        } else {
            $metadata->merge($toAdd);
        }
    }

    /**
     * Attempts to load a Metadata instance from a memory or cache source.
     *
     * @param   string  $type
     * @return  EntityMetadata|null
     */
    private function doLoadMetadata($type)
    {
        if (null !== $meta = $this->getFromMemory($type)) {
            // Found in memory.
            return $meta;
        }

        if (null !== $meta = $this->getFromCache($type)) {
            // Found in cache.
            $this->setToMemory($meta);
            return $meta;
        }
        return null;
    }

    /**
     * Puts the Metadata instance into a cache source (if set) and memory.
     *
     * @param   EntityMetadata  $metadata
     * @return  self
     */
    private function doPutMetadata(EntityMetadata $metadata)
    {
        if (true === $this->hasCache()) {
            $this->cache->putMetadataInCache($metadata);
        }
        $this->setToMemory($metadata);
        return $this;
    }

    /**
     * Clears any loaded metadata objects from memory.
     *
     * @return  self
     */
    public function clearMemory()
    {
        $this->loaded = [];
        return $this;
    }

    /**
     * Gets a Metadata instance for a type from memory.
     *
     * @return  EntityMetadata|null
     */
    private function getFromMemory($type)
    {
        if (isset($this->loaded[$type])) {
            return $this->loaded[$type];
        }
        return null;
    }

    /**
     * Sets a Metadata instance to the memory cache.
     *
     * @param   EntityMetadata  $metadata
     * @return  self
     */
    private function setToMemory(EntityMetadata $metadata)
    {
        $this->loaded[$metadata->type] = $metadata;
        return $this;
    }

    /**
     * Retrieves a Metadata instance for a type from cache.
     *
     * @return  EntityMetadata|null
     */
    private function getFromCache($type)
    {
        if (false === $this->hasCache()) {
            return null;
        }
        return $this->cache->loadMetadataFromCache($type);
    }
}
