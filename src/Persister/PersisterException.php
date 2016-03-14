<?php

namespace As3\Modlr\Persister;

use As3\Modlr\Exception\AbstractHttpException;

/**
 * Persister exceptions.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class PersisterException extends AbstractHttpException
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
                'Oops! We were unable to handle database operations for this record. %s',
                $message
            ),
            400,
            __FUNCTION__
        );
    }

    public static function persisterNotFound($key)
    {
        return new self(
            sprintf(
                'Unable to handle database operations. No persister found for type "%s"',
                $key
            ),
            500,
            __FUNCTION__
        );
    }

    public static function nyi($featureDescription)
    {
        return new self(
            sprintf(
                'Oops! A feature was accessed that is currently unimplemented: %s',
                $featureDescription
            ),
            500,
            __FUNCTION__
        );
    }
}
