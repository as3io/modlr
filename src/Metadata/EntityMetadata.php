<?php

namespace As3\Modlr\Metadata;

use As3\Modlr\Exception\MetadataException;

/**
 * Defines the metadata for an entity (e.g. a database object).
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 * @todo    This should be renamed to ModelMetadata.
 */
class EntityMetadata implements Interfaces\AttributeInterface, Interfaces\EmbedInterface, Interfaces\MergeableInterface, Interfaces\MixinInterface, Interfaces\RelationshipInterface
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
     * Uses relationships.
     */
    use Traits\RelationshipsTrait;

    /**
     * The id key name and type.
     */
    const ID_KEY  = 'id';
    const ID_TYPE = 'string';

    /**
     * The model type key.
     */
    const TYPE_KEY = 'type';

    /**
     * Whether this class is abstract.
     *
     * @var bool
     */
    public $abstract = false;

    /**
     * An array of attribute default values for this model.
     * Keyed by field name.
     *
     * @var array
     */
    public $defaultValues = [];

    /**
     * The entity type this entity extends.
     *
     * @var bool
     */
    public $extends;

    /**
     * Child entity types this entity owns.
     * Only used for polymorphic entities.
     *
     * @var array
     */
    public $ownedTypes = [];

    /**
     * The persistence metadata for this entity.
     *
     * @var StorageLayerInterface
     */
    public $persistence;

    /**
     * Whether this class is considered polymorphic.
     *
     * @var bool
     */
    public $polymorphic = false;

    /**
     * The search metadata for this entity.
     *
     * @var StorageLayerInterface
     */
    public $search;

    /**
     * Uniquely defines the type of entity.
     *
     * @var string
     */
    public $type;

    /**
     * Constructor.
     *
     * @param   string  $type   The resource identifier type.
     */
    public function __construct($type)
    {
        $this->setType($type);
    }

    /**
     * Gets the parent entity type.
     * For entities that are extended.
     *
     * @return  string|null
     */
    public function getParentEntityType()
    {
        return $this->extends;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return array_merge($this->getAttributes(), $this->getRelationships(), $this->getEmbeds());
    }

    /**
     * Whether this metadata represents an abstract class.
     *
     * @return  bool
     */
    public function isAbstract()
    {
        return (Boolean) $this->abstract;
    }

    /**
     * Whether this metadata represents a polymorphic class.
     *
     * @return  bool
     */
    public function isPolymorphic()
    {
        return (Boolean) $this->polymorphic;
    }

    /**
     * Deteremines whether search is enabled for this model.
     *
     * @return  bool
     */
    public function isSearchEnabled()
    {
        return null !== $this->search;
    }

    /**
     * Determines if this is a child entity of another entity.
     *
     * @return  bool
     */
    public function isChildEntity()
    {
        return null !== $this->getParentEntityType();
    }

    /**
     * {@inheritDoc}
     */
    public function merge(Interfaces\MergeableInterface $metadata)
    {
        if (!$metadata instanceof EntityMetadata) {
            throw new MetadataException('Unable to merge metadata. The provided metadata instance is not compatible.');
        }

        $this->setType($metadata->type);
        $this->setPolymorphic($metadata->isPolymorphic());
        $this->setAbstract($metadata->isAbstract());
        $this->extends = $metadata->extends;
        $this->ownedTypes = $metadata->ownedTypes;
        $this->defaultValues = array_merge($this->defaultValues, $metadata->defaultValues);

        $this->persistence->merge($metadata->persistence);
        $this->search->merge($metadata->search);

        $this->mergeAttributes($metadata->getAttributes());
        $this->mergeRelationships($metadata->getRelationships());
        $this->mergeEmbeds($metadata->getEmbeds());
        $this->mergeMixins($metadata->getMixins());

        return $this;
    }

    /**
     * Sets this metadata as representing an abstract class.
     *
     * @param   bool    $bit
     * @return  self
     */
    public function setAbstract($bit = true)
    {
        $this->abstract = (Boolean) $bit;
        return $this;
    }

    /**
     * Sets the persistence metadata for this entity.
     *
     * @param   Interfaces\StorageLayerInterface    $persistence
     * @return  self
     */
    public function setPersistence(Interfaces\StorageLayerInterface $persistence)
    {
        $this->persistence = $persistence;
        return $this;
    }

    /**
     * Sets this metadata as representing a polymorphic class.
     *
     * @param   bool    $bit
     * @return  self
     */
    public function setPolymorphic($bit = true)
    {
        $this->polymorphic = (Boolean) $bit;
        return $this;
    }

    /**
     * Sets the search metadata for this entity.
     *
     * @param   Interfaces\StorageLayerInterface    $search
     * @return  self
     */
    public function setSearch(Interfaces\StorageLayerInterface $search)
    {
        $this->search = $search;
        return $this;
    }

    /**
     * Sets the entity type.
     *
     * @param   string  $type
     * @return  self
     * @throws  MetadataException   If the type is not a string or is empty.
     */
    public function setType($type)
    {
        if (!is_string($type) || empty($type)) {
            throw MetadataException::invalidEntityType($type);
        }
        $this->type = $type;
        return $this;
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
        foreach ($mixin->getRelationships() as $relationship) {
            if (true === $this->hasRelationship($relationship->key)) {
                throw MetadataException::mixinPropertyExists($this->type, $mixin->name, 'relationship', $relationship->key);
            }
            $this->addRelationship($relationship);
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
        if (true === $this->hasRelationship($attribute->getKey())) {
            throw MetadataException::fieldKeyInUse('attribute', 'relationship', $attribute->getKey(), $this->type);
        }
        if (true === $this->hasEmbed($attribute->getKey())) {
            throw MetadataException::fieldKeyInUse('attribute', 'embed', $attribute->getKey(), $this->type);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateEmbed(EmbeddedPropMetadata $embed)
    {
        if (true === $this->hasAttribute($embed->getKey())) {
            throw MetadataException::fieldKeyInUse('embed', 'attribute', $embed->getKey(), $this->type);
        }
        if (true === $this->hasRelationship($embed->getKey())) {
            throw MetadataException::fieldKeyInUse('embed', 'relationship', $embed->getKey(), $this->type);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateRelationship(RelationshipMetadata $relationship)
    {
        if (true === $this->hasAttribute($relationship->getKey())) {
            throw MetadataException::fieldKeyInUse('relationship', 'attribute', $relationship->getKey(), $this->type);
        }
        if (true === $this->hasEmbed($relationship->getKey())) {
            throw MetadataException::fieldKeyInUse('relationship', 'embed', $relationship->getKey(), $this->type);
        }
    }

    /**
     * Merges attributes with this instance's attributes.
     *
     * @param   array   $toAdd
     * @return  self
     */
    private function mergeAttributes(array $toAdd)
    {
        foreach ($toAdd as $attribute) {
            $this->addAttribute($attribute);
        }
        return $this;
    }

    /**
     * Merges embeds with this instance's embeds.
     *
     * @param   array   $toAdd
     * @return  self
     */
    private function mergeEmbeds(array $toAdd)
    {
        foreach ($toAdd as $embed) {
            $this->addEmbed($embed);
        }
        return $this;
    }

    /**
     * Merges mixins with this instance's mixins.
     *
     * @param   array   $toAdd
     * @return  self
     */
    private function mergeMixins(array $toAdd)
    {
        foreach ($toAdd as $mixin) {
            if (!isset($this->mixins[$mixin->name])) {
                $this->mixins[$mixin->name] = $mixin;
            }
        }
        return $this;
    }

    /**
     * Merges relationships with this instance's relationships.
     *
     * @param   array   $toAdd
     * @return  self
     */
    private function mergeRelationships(array $toAdd)
    {
        foreach ($toAdd as $relationship) {
            $this->addRelationship($relationship);
        }
        return $this;
    }
}
