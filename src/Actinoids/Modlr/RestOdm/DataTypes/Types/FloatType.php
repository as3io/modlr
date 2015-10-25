<?php

namespace Actinoids\Modlr\RestOdm\DataTypes\Types;

/**
 * The float data type converter.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class FloatType implements TypeInterface
{
    /**
     * {@inheritDoc}
     */
    public function convertToSerializedValue($value)
    {
        if (null === $value) {
            return $value;
        }
        if (is_object($value)) {
            $value = (String) $value;
        }
        return (Float) $value;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToNormalizedValue($value)
    {
        if (null === $value) {
            return $value;
        }
        if (is_object($value)) {
            $value = (String) $value;
        }
        return (Float) $value;
    }
}
