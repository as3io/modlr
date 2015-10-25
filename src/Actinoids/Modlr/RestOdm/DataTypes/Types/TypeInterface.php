<?php

namespace Actinoids\Modlr\RestOdm\DataTypes\Types;

/**
 * The type interface that all data type objects must use.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface TypeInterface
{
    /**
     * Converts the value to the serialized (external) value.
     *
     * @param   mixed   $value
     * @return  mixed
     */
    public function convertToSerializedValue($value);

    /**
     * Converts the value to the normalized (internal) value.
     *
     * @param   mixed   $value
     * @return  mixed
     */
    public function convertToNormalizedValue($value);
}
