<?php

namespace As3\Modlr\Metadata\Cache;

use As3\Modlr\Metadata\MetadataFactory;

/**
 * Warms up the metadata cache objects by placing all known entities into the cache source.
 * Only functions if the MetadataFactory has cache implemented.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class CacheWarmer
{
    /**
     * The metadata factory for loading and caching metadata objects.
     *
     * @var MetadataFactory
     */
    private $mf;

    /**
     * Constructor.
     *
     * @param   MetadataFactory     $mf
     */
    public function __construct(MetadataFactory $mf)
    {
        $this->mf = $mf;
    }

    /**
     * Clears metadata objects from the cache.
     *
     * @param   string|array|null   $type
     * @return  array
     */
    public function clear($type = null)
    {
        if (false === $this->mf->hasCache()) {
            return [];
        }
        return $this->doClear($this->getTypes($type));
    }

    /**
     * Warms up metadata objects into the cache.
     *
     * @param   string|array|null   $type
     * @return  array
     */
    public function warm($type = null)
    {
        if (false === $this->mf->hasCache()) {
            return [];
        }
        return $this->doWarm($this->getTypes($type));
    }

    /**
     * Clears metadata objects for the provided model types.
     *
     * @param   array   $types
     * @return  array
     */
    private function doClear(array $types)
    {
        $cleared = [];
        $this->mf->enableCache(false);

        foreach ($types as $type) {
            $metadata = $this->mf->getMetadataForType($type);
            $this->mf->getCache()->evictMetadataFromCache($metadata);
            $cleared[] = $type;
        }

        $this->mf->enableCache(true);
        $this->mf->clearMemory();
        return $cleared;
    }

    /**
     * Warms up the metadata objects for the provided model types.
     *
     * @param   array   $types
     * @return  array
     */
    private function doWarm(array $types)
    {
        $warmed = [];
        $this->doClear($types);
        foreach ($types as $type) {
            $this->mf->getMetadataForType($type);
            $warmed[] = $type;
        }
        return $warmed;
    }

    /**
     * Gets the model types based on an array, string, or null type value.
     *
     * @param   string|array|null
     * @return  array
     */
    private function getTypes($type = null)
    {
        if (null === $type) {
            return $this->mf->getAllTypeNames();
        }
        return (array) $type;
    }
}
