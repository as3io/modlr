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
     * Warms up all metadata objects into the cache.
     *
     * @return  array
     */
    public function warm()
    {
        $warmed = [];
        if (false === $this->mf->hasCache()) {
            return $warmed;
        }

        $this->clear();
        foreach ($this->mf->getAllTypeNames() as $type) {
            $this->mf->getMetadataForType($type);
            $warmed[] = $type;
        }
        return $warmed;
    }

    /**
     * Clears all metadata objects from the cache.
     *
     * @return  array
     */
    public function clear()
    {
        $cleared = [];
        if (false === $this->mf->hasCache()) {
            return $cleared;
        }

        $this->mf->enableCache(false);
        foreach ($this->mf->getAllTypeNames() as $type) {
            $metadata = $this->mf->getMetadataForType($type);
            $this->mf->getCache()->evictMetadataFromCache($metadata);
            $cleared[] = $type;
        }
        $this->mf->enableCache(true);
        $this->mf->clearMemory();
        return $cleared;
    }
}
