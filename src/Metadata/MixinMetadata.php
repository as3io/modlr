<?php

namespace As3\Modlr\Metadata;

use As3\Modlr\Exception\MetadataException;

/**
 * Defines the metadata for an entity mixin.
 * A mixin is like a PHP trait, in that properties (attributes and relationships) can be reused by multiple models.
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class MixinMetadata implements Interfaces\AttributeInterface, Interfaces\EmbedInterface, Interfaces\RelationshipInterface
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
     * Uses merged properties.
     */
    use Traits\PropertiesTrait;

    /**
     * Uses relationships.
     */
    use Traits\RelationshipsTrait;

    /**
     * The mixin name/key.
     *
     * @var string
     */
    public $name;

    /**
     * Constructor.
     *
     * @param   string  $name   The mixin name.
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
        return array_merge($this->getAttributes(), $this->getRelationships(), $this->getEmbeds());
    }

    /**
     * {@inheritdoc}
     */
    protected function applyMixinProperties(MixinMetadata $mixin)
    {
        foreach ($mixin->getAttributes() as $attribute) {
            if (true === $this->hasAttribute($attribute->key)) {
                throw MetadataException::mixinPropertyExists($this->name, $mixin->name, 'attribute', $attribute->key);
            }
            $this->addAttribute($attribute);
        }
        foreach ($mixin->getRelationships() as $relationship) {
            if (true === $this->hasRelationship($relationship->key)) {
                throw MetadataException::mixinPropertyExists($this->name, $mixin->name, 'relationship', $relationship->key);
            }
            $this->addRelationship($relationship);
        }
        foreach ($mixin->getEmbeds() as $embed) {
            if (true === $this->hasEmbed($embed->key)) {
                throw MetadataException::mixinPropertyExists($this->name, $mixin->name, 'embed', $embed->key);
            }
            $this->addEmbed($embed);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAttribute(AttributeMetadata $attribute)
    {
        if (true === $this->hasRelationship($attribute->getKey())) {
            throw MetadataException::fieldKeyInUse('attribute', 'relationship', $attribute->getKey(), $this->name);
        }
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
        if (true === $this->hasRelationship($embed->getKey())) {
            throw MetadataException::fieldKeyInUse('embed', 'relationship', $embed->getKey(), $this->name);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateRelationship(RelationshipMetadata $relationship)
    {
        if (true === $this->hasAttribute($relationship->getKey())) {
            throw MetadataException::fieldKeyInUse('relationship', 'attribute', $relationship->getKey(), $this->name);
        }
        if (true === $this->hasEmbed($relationship->getKey())) {
            throw MetadataException::fieldKeyInUse('relationship', 'embed', $relationship->getKey(), $this->name);
        }
    }
}
