<?php

namespace Actinoids\Modlr\RestOdm\Search;

use Actinoids\Modlr\RestOdm\Exception\AbstractHttpException;

/**
 * Search client exceptions.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class ClientException extends AbstractHttpException
{
    public static function clientNotFound($key)
    {
        return new self(
            sprintf(
                'Unable to handle search operations. No search client found for type "%s"',
                $key
            ),
            500,
            __FUNCTION__
        );
    }
}
