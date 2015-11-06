<?php

namespace Actinoids\Modlr\RestOdm\DataTypes\Types;

/**
 * The object data type converter.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class ObjectType implements TypeInterface
{
    /**
     * {@inheritDoc}
     */
    public function convertToModlrValue($value)
    {
        if (empty($value)) {
            return null;
        }
        return (Object) $value;
    }
}
