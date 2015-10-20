<?php

namespace Actinoids\Modlr\RestOdm\Metadata;

use Actinoids\Modlr\RestOdm\DataTypes\TypeFactory;
use Actinoids\Modlr\RestOdm\Exception\MetadataException;
use Actinoids\Modlr\RestOdm\Metadata\Driver\DriverInterface;
use Actinoids\Modlr\RestOdm\Metadata\Cache\CacheInterface;
use Actinoids\Modlr\RestOdm\Util\NameFormatter;

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
     * Factory containing all valid attribute data types.
     *
     * @var TypeFactory
     */
    private $typeFactory;

    /**
     * Entity name formatting service, for entity types and field keys.
     *
     * @var NameFormatter
     */
    private $nameFormatter;

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
    public function __construct(DriverInterface $driver, TypeFactory $typeFactory, NameFormatter $nameFormatter)
    {
        $this->driver = $driver;
        $this->typeFactory = $typeFactory;
        $this->nameFormatter = $nameFormatter;
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
        $type = $this->nameFormatter->formatEntityType($type);
        if (null !== $metadata = $this->doLoadMetadata($type)) {
            // Found in memory or from cache implementation
            return $metadata;
        }

        // Loop through the type hierarchy (extension) and merge metadata objects.
        foreach ($this->driver->getTypeHierarchy($type) as $hierType) {

            $hierType = $this->nameFormatter->formatEntityType($hierType);
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

            $this->validateMetadata($hierType, $loaded);

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
     * Validates Metadata properties.
     *
     * @todo    This should go into a seperate validator class.
     * @param   string          $type
     * @param   EntityMetadata  $metadata
     */
    public function validateMetadata($type, EntityMetadata $metadata)
    {
        // if (!preg_match($memberPattern, $type)) {
        //     throw MetadataException::invalidMetadata($type, sprintf('The name "%s" is invalid. Only letters and underscores (not as the first or last character) are allowed.', $type));
        // }

        if ($type !== $metadata->type) {
            throw MetadataException::invalidMetadata($type, 'Metadata type mismatch.');
        }

        $validIdStrategies = ['object'];
        if (!in_array($metadata->idStrategy, $validIdStrategies)) {
            throw MetadataException::invalidMetadata($type, sprintf('The id strategy "%s" is invalid. Valid types are "%s"', $metadata->idStrategy, implode('", "', $validIdStrategies)));
        }

        // var_dump($metadata->isChildEntity(), $metadata->type);
        // die();

        if (false === $metadata->isChildEntity() && (empty($metadata->db) || empty($metadata->collection))) {
            throw MetadataException::invalidMetadata($type, 'The database and collection names cannot be empty.');
        }

        if (true === $metadata->isChildEntity()) {
            if (true === $metadata->isPolymorphic()) {
                throw MetadataException::invalidMetadata($type, 'An entity cannot both be polymorphic and be a child.');
            }
            if ($metadata->extends === $metadata->type) {
                throw MetadataException::invalidMetadata($type, 'An entity cannot extend itself.');
            }
            $allTypes = $this->getAllTypeNames();
            if (!in_array($metadata->extends, $allTypes)) {
                throw MetadataException::invalidMetadata($type, sprintf('The entity extension type "%s" does not exist.', $metadata->extends));
            }
            $parent = $this->doLoadMetadata($metadata->extends);
            if (false === $parent->isPolymorphic()) {
                throw MetadataException::invalidMetadata($type, sprintf('Parent classes must be polymorphic. Parent entity "%s" is not polymorphic.', $metadata->extends));
            }
        }

        foreach ($metadata->getAttributes() as $attribute) {
            if (empty($attribute->key)) {
                throw MetadataException::invalidMetadata($type, 'All fields must contain a key');
            }

            // @todo Validate field key
            // $this->nameFormatter->validateFieldKey($attribute)

            // if (!preg_match($memberPattern, $attribute->key)) {
            //     throw MetadataException::invalidMetadata($type, sprintf('The field key "%s" is invalid. Only letters and underscores (not as the first or last character) are allowed.', $attribute->key));
            // }

            if (false === $this->typeFactory->hasType($attribute->dataType)) {
                throw MetadataException::invalidMetadata($type, sprintf('The data type "%s" for attribute "%s" is invalid', $attribute->dataType, $attribute->getKey()));
            }

            if (true === $metadata->isChildEntity()) {
                if ($parent->hasAttribute($attribute->key)) {
                    throw MetadataException::invalidMetadata($type, sprintf('Parent entity type "%s" already contains field "%s"', $parent->type, $attribute->key));
                }
            }

            $todo = ['object', 'array'];
            if (in_array($attribute->dataType, $todo)) {
                throw MetadataException::invalidMetadata($type, 'NYI: Object and array attribute types still need expanding!!');
            }
        }

        foreach ($metadata->getRelationships() as $relationship) {
            throw MetadataException::invalidMetadata($type, 'NYI: Relationship metadata must still be validated!!');

            if (!preg_match($memberPattern, $relationship->key)) {
                throw MetadataException::invalidMetadata($type, sprintf('The field key "%s" is invalid. Only letters and underscores (not as the first or last character) are allowed.', $attribute->key));
            }
        }
        return true;
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
