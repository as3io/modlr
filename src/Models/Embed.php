<?php

namespace As3\Modlr\Models;

use As3\Modlr\Metadata\EmbedMetadata;
use As3\Modlr\Persister\Record;
use As3\Modlr\Store\Store;

/**
 * Represents an embedded record fragment of a root level model.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Embed extends AbstractModel
{
    /**
     * Constructor.
     *
     * @param   EmbedMetadata   $metadata   The internal embed metadata that supports this Embed.
     * @param   Store           $store      The model store service for handling persistence operations.
     * @param   array|null      $properties The embed's properties from the db layer to init the embed with. New embeds will constructed with a null record.
     */
    public function __construct(EmbedMetadata $metadata, Store $store, array $properties = null)
    {
        parent::__construct($metadata, $store, $properties);
        $this->getState()->setLoaded();
    }

    /**
     * Gets the embed name.
     *
     * @api
     * @return  string
     */
    public function getName()
    {
        return $this->metadata->name;
    }
}
