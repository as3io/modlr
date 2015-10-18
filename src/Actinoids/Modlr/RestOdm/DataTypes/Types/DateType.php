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
    public function convertToModlrValue($value)
    {
        if (null === $value) {
            return null;
        }
        return $this->createDateTime($value)->format(DateTime::RFC2822);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value)
    {
        if (null === $value) {
            return null;
        }
        return $this->createDateTime($value);
    }

    /**
     * Creates a DateTime object based on a value.
     *
     * @param   mixed   $value
     * @return  DateTime
     */
    private function createDateTime($value)
    {
        $date = new DateTime();
        if ($value instanceof DateTime) {
            $date = $value;
        } elseif ($value instanceof \MongoDate) {
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
