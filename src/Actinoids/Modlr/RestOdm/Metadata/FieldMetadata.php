<?php

namespace Actinoids\Modlr\RestOdm\Metadata;

use Actinoids\Modlr\RestOdm\Exception\MetadataException;

/**
 * Abstract field metadata class used for all Entity fields (attributes and relationships).
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class FieldMetadata
{
    /**
     * The field key.
     *
     * @var string
     */
    public $key;

    /**
     * Constructor.
     *
     * @param   string  $key
     */
    public function __construct($key)
    {
        $this->validateKey($key);
        $this->key = $key;
    }

    /**
     * Gets the field key.
     *
     * @return  string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Validates that the field key is not reserved.
     *
     * @param   string  $key
     * @throws  MetadataException
     */
    protected function validateKey($key)
    {
        $reserved = ['type', 'id'];
        if (in_array(strtolower($key), $reserved)) {
            throw MetadataException::reservedFieldKey($key, $reserved);
        }
    }
}
