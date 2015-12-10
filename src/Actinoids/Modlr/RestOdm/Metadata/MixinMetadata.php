<?php

namespace Actinoids\Modlr\RestOdm\Metadata;

use Actinoids\Modlr\RestOdm\Exception\MetadataException;
use Actinoids\Modlr\RestOdm\Metadata\Interfaces\AttributeInterface;
use Actinoids\Modlr\RestOdm\Metadata\Interfaces\RelationshipInterface;

/**
 * Defines the metadata for an entity mixin.
 * A mixin is like a PHP trait, in that properties (attributes and relationships) can be reused by multiple models.
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class MixinMetadata implements AttributeInterface, RelationshipInterface
{
    use Traits\PropertiesTrait;

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
}
