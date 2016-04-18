<?php

namespace As3\Modlr\Metadata;

use As3\Modlr\Exception\MetadataException;

/**
 * Defines the metadata for an embed.
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class EmbedMetadata implements Interfaces\AttributeInterface, Interfaces\EmbedInterface, Interfaces\MixinInterface
{
    /**
     * Uses attributes.
     */
    use Traits\AttributesTrait;

    /**
     * Uses embeds.
     */
    use Traits\EmbedsTrait;

    /**
     * Uses mixins.
     */
    use Traits\MixinsTrait;

    /**
     * Uses merged properties.
     */
    use Traits\PropertiesTrait;

    /**
     * The embed name/key.
     *
     * @var string
     */
    public $name;

    /**
     * Constructor.
     *
     * @param   string  $name   The embed name.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return array_merge($this->getAttributes(), $this->getEmbeds());
    }

    /**
     * {@inheritdoc}
     */
    protected function applyMixinProperties(MixinMetadata $mixin)
    {
        foreach ($mixin->getAttributes() as $attribute) {
            if (true === $this->hasAttribute($attribute->key)) {
                throw MetadataException::mixinPropertyExists($this->type, $mixin->name, 'attribute', $attribute->key);
            }
            $this->addAttribute($attribute);
        }
        foreach ($mixin->getEmbeds() as $embed) {
            if (true === $this->hasEmbed($embed->key)) {
                throw MetadataException::mixinPropertyExists($this->type, $mixin->name, 'embed', $embed->key);
            }
            $this->addEmbed($embed);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAttribute(AttributeMetadata $attribute)
    {
        if (true === $this->hasEmbed($attribute->getKey())) {
            throw MetadataException::fieldKeyInUse('attribute', 'embed', $attribute->getKey(), $this->name);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateEmbed(EmbeddedPropMetadata $embed)
    {
        if (true === $this->hasAttribute($embed->getKey())) {
            throw MetadataException::fieldKeyInUse('embed', 'attribute', $embed->getKey(), $this->name);
        }
    }
}
