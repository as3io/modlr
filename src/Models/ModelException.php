<?php

namespace As3\Modlr\Store;

use As3\Modlr\Exception\AbstractHttpException;

/**
 * Persister exceptions.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class ModelException extends AbstractHttpException
{
    public static function cannotModifyInverse(Model $model, $fieldKey)
    {
        return new self(
            sprintf(
                'Oops! The requested action for model type "%s" is invalid. The relationship field "%s" is inversed (not owned) and cannot be modified.',
                $model->getType(),
                $fieldKey
            ),
            400,
            __FUNCTION__
        );
    }
    public static function invalid(Model $model, $message = null)
    {
        return new self(
            sprintf(
                'Oops! The requested action for model type "%s" is invalid. %s',
                $model->getType(),
                $message
            ),
            400,
            __FUNCTION__
        );
    }
}
