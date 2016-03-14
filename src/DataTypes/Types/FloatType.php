<?php

namespace As3\Modlr\DataTypes\Types;

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
    public function convertToModlrValue($value)
    {
        if (null === $value) {
            return $value;
        }
        return (Float) (String) $value;
    }
}
