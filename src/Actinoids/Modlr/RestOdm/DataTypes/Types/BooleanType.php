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
    public function convertToModlrValue($value)
    {
        if (null === $value) {
            return $value;
        }
        return (Boolean) $value;
    }
}
