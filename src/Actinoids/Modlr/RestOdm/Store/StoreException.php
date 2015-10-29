<?php

namespace Actinoids\Modlr\RestOdm\Store;

use Actinoids\Modlr\RestOdm\Exception\AbstractHttpException;

/**
 * Store exceptions.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class StoreException extends AbstractHttpException
{
    public static function recordNotFound($type, $identifer)
    {
        return new self(
            sprintf(
                'No record was found for "%s" using id "%s"',
                $type,
                $identifer
            ),
            404,
            __FUNCTION__
        );
    }

    public static function badRequest($message = null)
    {
        return new self(
            sprintf(
                'Oops! We were unable to handle store operations for this record. %s',
                $message
            ),
            500,
            __FUNCTION__
        );
    }

    public static function invalidInclude($type, $fieldKey)
    {
        return new self(
            sprintf(
                'The relationship key "%s" was not found on entity "%s"',
                $fieldKey,
                $type
            ),
            400,
            __FUNCTION__
        );
    }

    public static function nyi($type)
    {
        return new self(
            sprintf(
                'Oops! A feature has been accessed while accessing "%s" that has not yet been completed.',
                $type
            ),
            500,
            __FUNCTION__
        );
    }
}
