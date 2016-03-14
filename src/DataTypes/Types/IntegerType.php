<?php

namespace As3\Modlr\RestOdm\DataTypes\Types;

/**
 * The integer data type converter.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class IntegerType implements TypeInterface
{
    /**
     * {@inheritDoc}
     */
    public function convertToModlrValue($value)
    {
        if (null === $value) {
            return $value;
        }
        return (Integer) (String) $value;
    }
}
