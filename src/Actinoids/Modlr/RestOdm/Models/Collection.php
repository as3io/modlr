<?php

namespace Actinoids\Modlr\RestOdm\Models;

/**
 * Model collection that contains record representations from a persistence (database) layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Collection extends AbstractCollection
{
    /**
     * Original models assigned to this collection.
     *
     * @var Model[]
     */
    protected $original = [];

    /**
     * Models added to this collection.
     * Tracks newly added models for rollback/change purposes.
     *
     * @var Model[]
     */
    protected $added = [];

    /**
     * Models removed from this collection.
     * Tracks removed models for rollback/change purposes.
     *
     * @var Model[]
     */
    protected $removed = [];

    /**
     * {@inheritDoc}
     */
    public function getQueryField()
    {
        return 'id';
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifiers($onlyUnloaded = true)
    {
        $identifiers = [];
        foreach ($this->models as $model) {
            if (true === $onlyUnloaded && true === $model->getState()->is('empty')) {
                $identifiers[] = $model->getId();
            }
        }
        return $identifiers;
    }

    /**
     * Adds an model to this collection.
     * Is used during initial collection construction.
     *
     * @param   Model   $model
     * @return  self
     */
    protected function add(Model $model)
    {
        parent::add($model);
        if (false === $this->hasOriginal($model)) {
            $this->original[] = $model;
        }
        return $this;
    }

    /**
     * Rollsback the collection it it's original state.
     *
     * @return  self
     */
    public function rollback()
    {
        $this->models = $this->original;
        $this->added = [];
        $this->removed = [];
        return $this;
    }

    /**
     * Clears/empties the collection.
     *
     * @return  self
     */
    public function clear()
    {
        $this->models = [];
        $this->added = [];
        $this->removed = $this->original;
        return $this;
    }

    /**
     * Pushes a Model into the collection.
     *
     * @param   Model   $model  The model to push.
     * @return  self
     */
    public function push(Model $model)
    {
        $this->validateAdd($model);
        if (true === $this->willAdd($model)) {
            return $this;
        }
        if (true === $this->willRemove($model)) {
            $this->evict('removed', $model);
            $this->set('models', $model);
            return $this;
        }
        if (true === $this->hasOriginal($model)) {
            return $this;
        }
        $this->set('added', $model);
        $this->set('models', $model);
        return $this;
    }

    /**
     * Removes a Model from the collection.
     *
     * @param   Model   $model  The model to push.
     * @return  self
     */
    public function remove(Model $model)
    {
        $this->validateAdd($model);
        if (true === $this->willRemove($model)) {
            return $this;
        }

        if (true === $this->willAdd($model)) {
            $this->evict('added', $model);
            $this->evict('models', $model);
            return $this;
        }

        if (true === $this->hasOriginal($model)) {
            $this->evict('models', $model);
            $this->set('removed', $model);
        }
        return $this;
    }


    /**
     * Determines if the Model is scheduled for removal from the collection.
     *
     * @param   Model   $model  The model to check.
     * @return  bool
     */
    public function willRemove(Model $model)
    {
        return -1 !== $this->indexOf('removed', $model);
    }

    /**
     * Determines if the Model is scheduled for addition to the collection.
     *
     * @param   Model   $model  The model to check.
     * @return  bool
     */
    public function willAdd(Model $model)
    {
        return -1 !== $this->indexOf('added', $model);
    }


    /**
     * Determines if the Model is included in the original set.
     *
     * @param   Model   $model  The model to check.
     * @return  bool
     */
    protected function hasOriginal(Model $model)
    {
        return -1 !== $this->indexOf('original', $model);
    }

    /**
     * Sets a Model to a collection property (original, added, removed, models).
     *
     * @param   string  $property   The property key
     * @param   Model   $model      The model to set.
     * @return  self
     */
    protected function set($property, Model $model)
    {
        $models = $this->$property;
        $models[] = $model;
        $this->$property = $models;
        return $this;
    }

    /**
     * Evicts a Model from a collection property (original, added, removed, models).
     *
     * @param   string  $property   The property key
     * @param   Model   $model      The model to set.
     * @return  self
     */
    protected function evict($property, Model $model)
    {
        $index = $this->indexOf($property, $model);
        $models = $this->$property;
        unset($models[$index]);
        $this->$property = array_values($models);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isDirty()
    {
        return !empty($this->added) || !empty($this->removed);
    }

    /**
     * Calculates the change set of this collection.
     *
     * @return  array
     */
    public function calculateChangeSet()
    {
        if (false === $this->isDirty()) {
            return [];
        }
        return [
            'old' => empty($this->original) ? null : $this->original,
            'new' => empty($this->models) ? null : $this->models,
        ];
    }
}
