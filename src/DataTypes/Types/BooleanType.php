<?php

namespace As3\Modlr\DataTypes\Types;

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
    public function convertToModlrValue($value)
    {
        if (null === $value) {
            return $value;
        }
        if ('true' === strtolower($value)) {
            return true;
        }
        if ('false' === strtolower($value)) {
            return false;
        }
        return (Boolean) $value;
    }
}
