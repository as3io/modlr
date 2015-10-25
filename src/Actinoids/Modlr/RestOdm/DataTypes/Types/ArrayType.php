<?php

namespace Actinoids\Modlr\RestOdm\DataTypes\Types;

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
    public function convertToSerializedValue($value)
    {
        if (empty($value)) {
            return [];
        }
        return array_values($this->extractArray($value));
    }

    /**
     * {@inheritDoc}
     */
    public function convertToNormalizedValue($value)
    {
        if (empty($value)) {
            return [];
        }
        return array_values($this->extractArray($value));
    }

    /**
     * Takes a value and converts it to an array.
     *
     * @param   mixed   $value
     * @return  array
     */
    protected function extractArray($value)
    {
        if ($value instanceof \Traversable) {
            $array = [];
            foreach ($value as $key => $value) {
                $array[$key] = $value;
            }
            return $array;
        }

        return (Array) $value;
    }
}
