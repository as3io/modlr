<?php

namespace As3\Modlr\Metadata\Interfaces;

use As3\Modlr\Metadata\EmbeddedPropMetadata;

/**
 * Interface for Metadata objects containing embeds.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface EmbedInterface extends PropertyInterface
{
    /**
     * Adds an embed field.
     *
     * @param   EmbeddedPropMetadata   $embed
     * @return  self
     */
    public function addEmbed(EmbeddedPropMetadata $embed);

    /**
     * Determines if an embed field exists.
     *
     * @param   string  $key
     * @return  bool
     */
    public function hasEmbed($key);

    /**
     * Determines any embed fields exist.
     *
     * @return  bool
     */
    public function hasEmbeds();

    /**
     * Gets an embed field.
     * Returns null if the embed does not exist.
     *
     * @param   string  $key
     * @return  EmbeddedPropMetadata|null
     */
    public function getEmbed($key);

    /**
     * Gets all embed fields.
     *
     * @return  EmbeddedPropMetadata[]
     */
    public function getEmbeds();
}
