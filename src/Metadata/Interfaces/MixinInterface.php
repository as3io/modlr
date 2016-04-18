<?php

namespace As3\Modlr\Metadata\Interfaces;

use As3\Modlr\Metadata\MixinMetadata;

/**
 * Interface for Metadata objects containing mixins.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface MixinInterface
{
    /**
     * Adds a mixin (and applies its properties) to the implementing object.
     *
     * @param   MixinMetadata   $mixin
     * @return  self
     */
    public function addMixin(MixinMetadata $mixin);

    /**
     * Determines if a mixin exists.
     *
     * @param   string  $mixinName
     * @return  bool
     */
    public function hasMixin($mixinName);

    /**
     * Gets all assigned mixins.
     *
     * @return  MixinMetadata[]
     */
    public function getMixins();
}
