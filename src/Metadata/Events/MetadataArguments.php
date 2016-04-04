<?php

namespace As3\Modlr\Metadata\Events;

use As3\Modlr\Events\EventArguments;
use As3\Modlr\Metadata\EntityMetadata;

/**
 * Store event constants.
 *
 * @author Josh Worden <solocommand@gmail.com>
 */
class MetadataArguments extends EventArguments
{
    /**
     * @var EntityMetadata
     */
    private $metadata;

    /**
     * Constructor.
     *
     * @param   EntityMetadata   $metadata
     */
    public function __construct(EntityMetadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Gets the metadata.
     *
     * @return  EntityMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}
