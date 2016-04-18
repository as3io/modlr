<?php

namespace As3\Modlr\Metadata;

use As3\Modlr\Exception\MetadataException;

/**
 * Defines metadata for an embedded field.
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class EmbeddedPropMetadata extends FieldMetadata
{
    /**
     * The embedded metadata for this embedded property.
     *
     * @var string
     */
    public $embedMeta;

    /**
     * The embed type: one or many
     *
     * @var string
     */
    public $embedType;

    /**
     * Constructor.
     *
     * @param   string          $key
     * @param   string          $embedType
     * @param   EmbedMetadata   $embedMeta
     * @param   bool            $mixin
     */
    public function __construct($key, $embedType, EmbedMetadata $embedMeta, $mixin = false)
    {
        $this->embedMeta = $embedMeta;
        $this->embedType = $embedType;
        parent::__construct($key, $mixin);
    }

    /**
     * Sets the embed type: one or many.
     *
     * @param   string  $relType
     * @return  self
     */
    public function setEmbedType($embedType)
    {
        $this->validateType($embedType);
        $this->embedType = $embedType;
        return $this;
    }

    /**
     * Validates the embed type.
     *
     * @param   string  $type
     * @return  bool
     * @throws  MetadataException
     */
    protected function validateType($embedType)
    {
        $valid = ['one', 'many'];
        if (!in_array($embedType, $valid)) {
            throw MetadataException::invalidRelType($embedType, $valid);
        }
        return true;
    }
}
