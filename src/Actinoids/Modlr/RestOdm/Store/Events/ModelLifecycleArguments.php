<?php

namespace Actinoids\Modlr\RestOdm\Store\Events;

use Actinoids\Modlr\RestOdm\Events\EventArguments;
use Actinoids\Modlr\RestOdm\Models\Model;

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
