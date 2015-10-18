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
        return $this->extractObject($value);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value)
    {
        return $this->extractObject($value);
    }

    /**
     * Takes a value and converts it to an object.
     *
     * @param   mixed   $value
     * @return  array
     */
    protected function extractObject($value)
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \Traversable) {
            $array = [];
            foreach ($value as $key => $value) {
                $array[$key] = $value;
            }
            return (Object) $array;
        }

        return (Object) $value;
    }
}
