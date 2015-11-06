<?php

namespace Actinoids\Modlr\RestOdm\DataTypes\Types;

/**
 * The type interface that all data type objects must use.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface TypeInterface
{
    /**
     * Converts the value to the internal, Modlr (PHP) value.
     *
     * @param   mixed   $value
     * @return  mixed
     */
    public function convertToModlrValue($value);
}
