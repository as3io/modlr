<?php

namespace Actinoids\Modlr\RestOdm\Api;

use Actinoids\Modlr\RestOdm\Exception\AbstractHttpException;

/**
 * Adapter exceptions.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class AdapterException extends AbstractHttpException
{
    public static function entityTypeNotFound($type)
    {
        return new self(
            sprintf(
                'No API resource was found for entity type "%s"',
                $type
            ),
            404,
            __FUNCTION__
        );
    }

    public static function badRequest($message = null)
    {
        return new self(
            sprintf(
                'Oops. We were unable to process this API request. %s',
                $message
            ),
            400,
            __FUNCTION__
        );
    }

    public static function requestPayloadNotFound($message = null)
    {
        return new self(
            sprintf(
                'No request payload was found with the request. %s',
                $message
            ),
            400,
            __FUNCTION__
        );
    }
}
