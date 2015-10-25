<?php

namespace Actinoids\Modlr\RestOdm\DataTypes\Types;

use \DateTime;

/**
 * The date data type converter.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class DateType implements TypeInterface
{
    /**
     * {@inheritDoc}
     */
    public function convertToSerializedValue($value)
    {
        if (null === $value) {
            return null;
        }
        return $this->createDateTime($value)->format(DateTime::RFC2822);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToNormalizedValue($value)
    {
        if (null === $value) {
            return null;
        }
        if ($value instanceof \MongoDate) {
            return $value;
        }
        return new \MongoDate($this->createDateTime($value)->getTimestamp());
    }

    /**
     * Creates a DateTime object based on a value.
     *
     * @param   mixed   $value
     * @return  DateTime
     */
    private function createDateTime($value)
    {
        if ($value instanceof DateTime) {
            return $value;
        }
        $date = new DateTime();
        if ($value instanceof \MongoDate) {
            $date->setTimestamp($value->sec);
        } elseif (is_object($value)) {
            $value = (String) $value;
            $timestamp = is_numeric($value) ? $value : strtotime($value);
            $date->setTimestamp($timestamp);
        } elseif (is_numeric($value)) {
            $date->setTimestamp($value);
        } else {
            $date = new DateTime($value);
        }
        return $date;
    }
}
