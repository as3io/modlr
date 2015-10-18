<?php

namespace Actinoids\Modlr\RestOdm\Metadata;

use Actinoids\Modlr\RestOdm\Exception\MetadataException;
use Actinoids\Modlr\RestOdm\Metadata\Driver\DriverInterface;
use Actinoids\Modlr\RestOdm\Metadata\Cache\CacheInterface;
use Actinoids\Modlr\RestOdm\Util\Inflector;

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
     * Inflector for formatting entity types.
     *
     * @var Inflector
     */
    private $inflector;

    /**
     * Constructor.
     *
     * @param   DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
        $this->inflector = new Inflector();
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

            // // Format (and validate) the external entity type and set.
            // $loaded->externalType = $this->entityFormatter->formatExternalEntityType($hierType);

            // // Format (and validate) the external field keys for attributes and relationships.
            // foreach ($loaded->getAttributes() as $attribute) {
            //     $attribute->externalKey = $this->entityFormatter->formatField($attribute->key);
            // }
            // foreach ($loaded->getRelationships() as $relationship) {
            //     $relationship->externalKey = $this->entityFormatter->formatField($relationship->key);
            // }

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
        $types = [];
        foreach ($this->driver->getAllTypeNames() as $type) {
            $types[] = $this->formatEntityType($type);
        }
        return $types;
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
        try {
            $this->getMetadataForType($type);
            return true;
        } catch (MetadataException $e) {
            return false;
        }
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
     * Formats the entity type.
     *
     * @param   string  $type
     * @return  string
     */
    private function formatEntityType($type)
    {
        $delim = EntityMetadata::NAMESPACE_DELIM;

        if (false === stristr($type, $delim)) {
            return $this->inflector->studlify($type);
        }
        $parts = explode($delim, $type);
        foreach ($parts as &$part) {
            $part = $this->inflector->studlify($part);
        }
        return implode($delim, $parts);
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
