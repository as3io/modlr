<?php

namespace As3\Modlr\Store\Events;

use As3\Modlr\Events\EventArguments;
use As3\Modlr\Models\Model;

/**
 * Store event constants.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class ModelLifecycleArguments extends EventArguments
{
    /**
     * @var Model
     */
    private $model;

    /**
     * Constructor.
     *
     * @param   Model   $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Gets the model.
     *
     * @return  Model
     */
    public function getModel()
    {
        return $this->model;
    }
}
