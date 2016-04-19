<?php

namespace As3\Modlr\Models\Collections;

use As3\Modlr\Models\Model;

/**
 * Model collection that contains record representations from a persistence (database) layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Collection extends ModelCollection
{
    /**
     * {@inheritDoc}
     */
    public function getIdentifiers($onlyUnloaded = true)
    {
        $identifiers = [];
        foreach ($this->models as $model) {
            if (!$model instanceof Model) {
                continue;
            }
            if (true === $onlyUnloaded && true === $model->getState()->is('empty')) {
                $identifiers[] = $model->getId();
            }
        }
        return $identifiers;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryField()
    {
        return 'id';
    }
}
