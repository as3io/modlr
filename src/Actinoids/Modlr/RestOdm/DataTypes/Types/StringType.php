<?php

namespace As3\Modlr\RestOdm\DataTypes\Types;

/**
 * The string data type converter.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class StringType implements TypeInterface
{
    /**
     * {@inheritDoc}
     */
    public function convertToModlrValue($value)
    {
        if (empty($value)) {
            return null;
        }
        return (String) $value;
    }
}
