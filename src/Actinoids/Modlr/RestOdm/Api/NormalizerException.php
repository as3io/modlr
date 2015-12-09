<?php

namespace Actinoids\Modlr\RestOdm\Api;

use Actinoids\Modlr\RestOdm\Exception\AbstractHttpException;

/**
 * Normalizer exceptions.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class NormalizerException extends AbstractHttpException
{
    public static function unableToNormalizePayload($type, $message = null)
    {
        return new self(
            sprintf(
                'Unable to normalize payload for entity type "%s". %s',
                $type,
                $message
            ),
            400,
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
}
