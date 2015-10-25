<?php

namespace Actinoids\Modlr\RestOdm\DataTypes\Types;

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
    public function convertToSerializedValue($value)
    {
        if (null === $value) {
            return $value;
        }
        if (is_object($value)) {
            $value = (String) $value;
        }
        return (Integer) $value;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToNormalizedValue($value)
    {
        if (null === $value) {
            return $value;
        }
        if ($value instanceof \MongoInt64) {
            return $value;
        }
        if (is_object($value)) {
            $value = (Integer) (String) $value;
        }
        return new \MongoInt64($value);
    }
}
