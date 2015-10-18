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
     * @param   string  $dataType   The attribute data type.
     */
    public function __construct($key, $dataType)
    {
        parent::__construct($key);
        $this->dataType = $dataType;
    }
}
