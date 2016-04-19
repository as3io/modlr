<?php

namespace As3\Modlr\Metadata\Traits;

use As3\Modlr\Exception\MetadataException;
use As3\Modlr\Metadata\MixinMetadata;

/**
 * Common mixin metadata get, set, and add methods.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
trait MixinsTrait
{
    /**
     * All mixins assigned to this object.
     *
     * @var     MixinMetadata[]
     */
    public $mixins = [];

    /**
     * Adds a mixin (and applies its properties) to this object.
     *
     * @param   MixinMetadata   $mixin
     * @return  self
     */
    public function addMixin(MixinMetadata $mixin)
    {
        if (isset($this->mixins[$mixin->name])) {
            return $this;
        }

        $this->applyMixinProperties($mixin);
        $this->mixins[$mixin->name] = $mixin;
        return $this;
    }

    /**
     * Determines if a mixin exists.
     *
     * @param   string  $mixinName
     * @return  bool
     */
    public function hasMixin($mixinName)
    {
        return isset($this->mixins[$mixinName]);
    }

    /**
     * Gets all assigned mixins.
     *
     * @return  MixinMetadata[]
     */
    public function getMixins()
    {
        return $this->mixins;
    }

    /**
     * Applies the mixin properties to the implementing object.
     *
     * @param   MixinMetadata
     * @throws  MetadataException
     * @return  self
     */
    protected abstract function applyMixinProperties(MixinMetadata $mixin);
}
