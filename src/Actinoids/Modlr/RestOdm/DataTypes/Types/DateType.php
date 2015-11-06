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
        if ($value instanceof DateTime) {
            return $value;
        }
        if ($value instanceof \MongoDate) {
            $dateStr = date('Y-m-d H:i:s.'.$value->usec, $value->sec);
            return new DateTime($dateStr);
        }
        if (is_numeric($value)) {
            // Supports microseconds
            $value = (Float) $value;
            $usec = round(($value - (Integer) $value) * 1000000, 0);
            $dateStr = date('Y-m-d H:i:s.'.$usec, (Integer) $value);
            return new DateTime($dateStr);
        }
        return new DateTime((String) $value);
    }
}
