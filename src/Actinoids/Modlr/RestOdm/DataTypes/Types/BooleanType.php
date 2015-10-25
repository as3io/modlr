<?php

namespace Actinoids\Modlr\RestOdm\DataTypes\Types;

/**
 * The boolean data type converter.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class BooleanType implements TypeInterface
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
        return (Boolean) $value;
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
        return (Boolean) $value;
    }
}
