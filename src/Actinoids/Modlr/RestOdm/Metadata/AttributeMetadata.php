<?php

namespace Actinoids\Modlr\RestOdm\Metadata;

/**
 * Defines metadata for a "standard" field.
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class AttributeMetadata extends FieldMetadata
{
    /**
     * The attribute type, such as string, integer, float, etc.
     *
     * @var string
     */
    public $dataType;

    /**
     * Constructor.
     *
     * @param   string  $key        The attribute field key.
     * @param   string  $dataType   The attribute data type.
     * @param   bool    $mixin
     */
    public function __construct($key, $dataType, $mixin = false)
    {
        parent::__construct($key, $mixin);
        $this->dataType = $dataType;
    }
}
