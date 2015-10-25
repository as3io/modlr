<?php

namespace Actinoids\Modlr\RestOdm\DataTypes\Types;

/**
 * The mixed data type converter.
 * Actually doesn't convert anything, just passes the raw value.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class MixedType implements TypeInterface
{
    /**
     * {@inheritDoc}
     */
    public function convertToSerializedValue($value)
    {
        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToNormalizedValue($value)
    {
        return $value;
    }
}
