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
        if (is_object($value)) {
            return (Boolean) (String) $value;
        }
        if (null !== $value) {
            return (Boolean) $value;
        }
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value)
    {
        if (is_object($value)) {
            return (Boolean) (String) $value;
        }
        if (null !== $value) {
            return (Boolean) $value;
        }
        return null;
    }
}
