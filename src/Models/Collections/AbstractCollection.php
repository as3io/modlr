<?php

namespace As3\Modlr\Models\Collections;

use \Countable;
use \Iterator;
use As3\Modlr\Models\AbstractModel;
use As3\Modlr\Store\Store;

/**
 * Collection that contains record representations from a persistence (database) layer.
 * These representations can either be of first-order Models or fragmentted Embeds.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class AbstractCollection implements Iterator, Countable
{
    /**
     * Models added to this collection.
     * Tracks newly added models for rollback/change purposes.
     *
     * @var Model[]
     */
    protected $added = [];

    /**
     * Whether the collection has been loaded with data from the persistence layer
     *
     * @var bool
     */
    protected $loaded = true;

    /**
     * Current models assigned to this collection.
     * Needed for iteration, access, and count purposes.
     *
     * @var AbstractModel[]
     */
    protected $models = [];

    /**
     * Original models assigned to this collection.
     *
     * @var Model[]
     */
    protected $original = [];

    /**
     * The array position.
     *
     * @var int
     */
    protected $pos = 0;

    /**
     * Models removed from this collection.
     * Tracks removed models for rollback/change purposes.
     *
     * @var Model[]
     */
    protected $removed = [];

    /**
     * The store for handling storage operations.
     *
     * @var Store
     */
    protected $store;

    /**
     * Constructor.
     *
     * @param   Store           $store
     * @param   AbstractModel[] $models
     */
    public function __construct(Store $store, array $models = [])
    {
        $this->pos = 0;
        $this->store = $store;
        $this->setModels($models);
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
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->models);
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->models[$this->pos];
    }

    /**
     * Gets a single model result from the collection.
     *
     * @return  AbstractModel|null
     */
    public function getSingleResult()
    {
        if (0 === $this->count()) {
            return null;
        }
        $this->rewind();
        return $this->current();
    }

    /**
     * Gets the model collection type.
     *
     * @return  string
     */
    abstract public function getType();

    /**
     * Determines if the Model is included in the collection.
     *
     * @param   AbstractModel   $model  The model to check.
     * @return  bool
     */
    public function has(AbstractModel $model)
    {
        return -1 !== $this->indexOf('models', $model);
    }

    /**
     * Determines if any models in this collection are dirty (have changes).
     *
     * @return  bool
     */
    public function hasDirtyModels()
    {
        foreach ($this->models as $model) {
            if (true === $model->getState()->is('dirty')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determines if the collection is dirty.
     *
     * @return  bool
     */
    public function isDirty()
    {
        return !empty($this->added) || !empty($this->removed);
    }

    /**
     * Determines if this collection is empty.
     *
     * @return  bool
     */
    public function isEmpty()
    {
        return 0 === $this->count();
    }

    /**
     * Determines if models in this collection have been loaded from the persistence layer.
     *
     * @return  bool
     */
    public function isLoaded()
    {
        return $this->loaded;
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->pos;
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        ++$this->pos;
    }

    /**
     * Pushes a Model into the collection.
     *
     * @param   AbstractModel   $model  The model to push.
     * @return  self
     */
    public function push(AbstractModel $model)
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
     * Removes a model from the collection.
     *
     * @param   AbstractModel   $model  The model to remove.
     * @return  self
     */
    public function remove(AbstractModel $model)
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
     * {@inheritDoc}
     */
    public function rewind()
    {
        $this->pos = 0;
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
     * {@inheritDoc}
     */
    public function valid()
    {
        return isset($this->models[$this->pos]);
    }

    /**
     * Determines if the model is scheduled for addition to the collection.
     *
     * @param   AbstractModel   $model  The model to check.
     * @return  bool
     */
    public function willAdd(AbstractModel $model)
    {
        return -1 !== $this->indexOf('added', $model);
    }

    /**
     * Determines if the model is scheduled for removal from the collection.
     *
     * @param   AbstractModel   $model  The model to check.
     * @return  bool
     */
    public function willRemove(AbstractModel $model)
    {
        return -1 !== $this->indexOf('removed', $model);
    }

    /**
     * Adds an model to this collection.
     * Is used during initial collection construction.
     *
     * @param   AbstractModel   $model
     * @return  self
     */
    protected function add(AbstractModel $model)
    {
        if (true === $this->has($model)) {
            return $this;
        }
        $this->validateAdd($model);
        if (true === $model->getState()->is('empty')) {
            $this->loaded = false;
        }
        $this->models[] = $model;
        if (false === $this->hasOriginal($model)) {
            $this->original[] = $model;
        }
        return $this;
    }

    /**
     * Evicts a model from a collection property (original, added, removed, models).
     *
     * @param   string          $property   The property key
     * @param   AbstractModel   $model      The model to set.
     * @return  self
     */
    protected function evict($property, AbstractModel $model)
    {
        $index = $this->indexOf($property, $model);
        $models = $this->$property;
        unset($models[$index]);
        $this->$property = array_values($models);
        return $this;
    }

    /**
     * Determines if the model is included in the original set.
     *
     * @param   AbstractModel   $model  The model to check.
     * @return  bool
     */
    protected function hasOriginal(AbstractModel $model)
    {
        return -1 !== $this->indexOf('original', $model);
    }

    /**
     * Gets the Model array index from a collection property (original, added, removed, models).
     * Will return -1 if the model was not found.
     *
     * @param   string          $property   The property key
     * @param   AbstractModel   $model      The model to check.
     * @return  int
     */
    protected function indexOf($property, AbstractModel $model)
    {
        $this->validateModelClass($model);

        // @todo For performance, can we create a map using the model's composite key to avoid these loops?
        foreach ($this->$property as $index => $loaded) {
            if (true === $this->modelsMatch($model, $loaded)) {
                return $index;
            }
        }
        return -1;
    }

    /**
     * Determines if the provided models match.
     *
     * @param   AbstractModel   $model
     * @param   AbstractModel   $loaded
     * @return  bool
     */
    abstract protected function modelsMatch(AbstractModel $model, AbstractModel $loaded);

    /**
     * Sets a model to a collection property (original, added, removed, models).
     *
     * @param   string          $property   The property key
     * @param   AbstractModel   $model      The model to set.
     * @return  self
     */
    protected function set($property, AbstractModel $model)
    {
        $models = $this->$property;
        $models[] = $model;
        $this->$property = $models;
        return $this;
    }

    /**
     * Sets an array of models to the collection.
     *
     * @param   AbstractModel[]     $models
     * @return  self
     */
    protected function setModels(array $models)
    {
        foreach ($models as $model) {
            $this->add($model);
        }
        return $this;
    }

    /**
     * Validates that the collection supports the incoming model.
     *
     * @param   AbstractModel   $model  The model to validate.
     * @throws  \InvalidArgumentException
     */
    abstract protected function validateAdd(AbstractModel $model);
}
