<?php

namespace Actinoids\Modlr\RestOdm\DataTypes\Types;

/**
 * The string data type converter.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class StringType implements TypeInterface
{
    /**
     * {@inheritDoc}
     */
    public function convertToSerializedValue($value)
    {
        if (empty($value)) {
            return null;
        }
        return (String) $value;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToNormalizedValue($value)
    {
        if (empty($value)) {
            return null;
        }
        return (String) $value;
    }
}
