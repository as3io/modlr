<?php

namespace As3\Modlr\Metadata\Cache;

use As3\Modlr\Metadata\EntityMetadata;

/**
 * Caches and retrieves EntityMetadata objects from the file system using igbinary serialization.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class BinaryFileCache extends FileCache
{
    /**
     * The cache type file prefix.
     *
     * @var string
     */
    protected $cachePrefix = 'BinaryCache';

    /**
     * The file extension.
     *
     * @var string
     */
    protected $extension = 'bin';

    /**
     * {@inheritDoc}
     */
    protected function readFile($file)
    {
        return igbinary_unserialize(file_get_contents($file));
    }

    /**
     * {@inheritDoc}
     */
    public function putMetadataInCache(EntityMetadata $metadata)
    {
        $this->writeFile($metadata, igbinary_serialize($metadata));
        return $this;
    }
}
