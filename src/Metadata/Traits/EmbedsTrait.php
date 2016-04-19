<?php

namespace As3\Modlr\Metadata\Traits;

use As3\Modlr\Exception\MetadataException;
use As3\Modlr\Metadata\EmbeddedPropMetadata;

/**
 * Common embedded property metadata get, set, and add methods.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
trait EmbedsTrait
{
    /**
     * All embed fields assigned to this metadata object.
     * An embed is a " complex object/attribute" field that supports defining sub-attributes.
     *
     * @var EmbeddedPropMetadata[]
     */
    public $embeds = [];

    /**
     * Adds an embed field.
     *
     * @param   EmbeddedPropMetadata   $embed
     * @return  self
     */
    public function addEmbed(EmbeddedPropMetadata $embed)
    {
        $this->validateEmbed($embed);
        $this->embeds[$embed->getKey()] = $embed;
        ksort($this->embeds);
        return $this;
    }

    /**
     * Determines if an embed field exists.
     *
     * @param   string  $key
     * @return  bool
     */
    public function hasEmbed($key)
    {
        return null !== $this->getEmbed($key);
    }

    /**
     * Determines any embed fields exist.
     *
     * @return  bool
     */
    public function hasEmbeds()
    {
        return !empty($this->embeds);
    }

    /**
     * Gets an embed field.
     * Returns null if the embed does not exist.
     *
     * @param   string  $key
     * @return  EmbeddedPropMetadata|null
     */
    public function getEmbed($key)
    {
        if (!isset($this->embeds[$key])) {
            return null;
        }
        return $this->embeds[$key];
    }

    /**
     * Gets all embed fields.
     *
     * @return  EmbeddedPropMetadata[]
     */
    public function getEmbeds()
    {
        return $this->embeds;
    }

    /**
     * Validates that the embed can be added.
     *
     * @param   EmbeddedPropMetadata    $embed
     * @return  self
     * @throws  MetadataException
     */
    abstract protected function validateEmbed(EmbeddedPropMetadata $embed);
}
