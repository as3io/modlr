<?php

namespace Actinoids\Modlr\RestOdm\Models\Relationships;

use Actinoids\Modlr\RestOdm\Models\Properties;

/**
 * Represents the has-many relationships of a Model.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class HasMany extends Properties
{
    /**
     * Extended to also check for dirty states of has-many Collections.
     *
     * {@inheritDoc}
     */
    public function areDirty()
    {
        if (true === parent::areDirty()) {
            return true;
        }
        foreach ($this->getOriginalAll() as $collection) {
            if (true === $collection->isDirty()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extended to also rollback changes of has-many collections.
     *
     * {@inheritDoc}
     */
    public function rollback()
    {
        parent::rollback();
        foreach ($this->getOriginalAll() as $collection) {
            $collection->rollback();
        }
        return $this;
    }

    /**
     * Overwritten to account for collection change sets.
     *
     * {@inheritDoc}
     */
    public function calculateChangeSet()
    {
        $set = [];
        foreach ($this->getOriginalAll() as $key => $collection) {
            if (false === $collection->isDirty()) {
                continue;
            }
            $set[$key] = $collection->calculateChangeSet();
        }
        return $set;
    }
}
