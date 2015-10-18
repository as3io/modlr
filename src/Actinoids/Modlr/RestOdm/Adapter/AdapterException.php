<?php

namespace Actinoids\Modlr\RestOdm\Adapter;

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
}
