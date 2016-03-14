<?php

namespace As3\Modlr\RestOdm\DataTypes\Types;

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
    public function convertToModlrValue($value)
    {
        return $value;
    }
}
