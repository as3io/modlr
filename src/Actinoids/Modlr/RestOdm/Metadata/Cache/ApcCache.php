<?php

namespace Actinoids\Modlr\RestOdm\Metadata\Cache;

use Redis;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;

/**
 * Caches and retrieves EntityMetadata objects from APC.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class ApcCache implements CacheInterface
{
    /**
     * The TTL of the cache, in seconds.
     *
     * @var int
     */
    private $ttl;

    /**
     * The cache key prefix to use.
     *
     * @var string
     */
     private $prefix = 'ModlrData';

    /**
     * Constructor.
     *
     * @param   Redis   $redis
     * @param   int     $ttl
     * @param   int     $serializer
     */
    public function __construct($ttl = 3600)
    {
        $this->ttl = (Integer) $ttl;
    }

    /**
     * Sets a custom cache key prefix.
     *
     * @param   string  $prefix
     * @return  self
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Gets the cache key based on entity type.
     *
     * @param   string  $type
     * @return  string
     */
    protected function getKey($type)
    {
        return sprintf('%s::%s', $this->prefix, $type);
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataFromCache($type)
    {
        $metadata = apc_fetch($this->getKey($type));
        if (!$metadata) {
            return null;
        }
        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function putMetadataInCache(EntityMetadata $metadata)
    {
        $r = apc_store($this->getKey($metadata->type), $metadata, $this->ttl);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function evictMetadataFromCache(EntityMetadata $metadata)
    {
        $r = apc_delete($this->getKey($metadata->type));
        return $this;
    }
}
