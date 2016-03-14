<?php

namespace As3\Modlr\RestOdm\DataTypes\Types;

/**
 * The array data type converter.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class ArrayType implements TypeInterface
{
    /**
     * {@inheritDoc}
     */
    public function convertToModlrValue($value)
    {
        if (empty($value)) {
            return [];
        }
        (Array) $value;
        return array_values($value);
    }
}
