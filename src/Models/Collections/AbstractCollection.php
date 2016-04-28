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
     * @var AbstractModel[]
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
     * Gets the position of each model key in the collection.
     *
     * @var string[]
     */
    protected $modelKeyMap = [];

    /**
     * Original models assigned to this collection.
     *
     * @var AbstractModel[]
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
     * @var AbstractModel[]
     */
    protected $removed = [];

    /**
     * The store for handling storage operations.
     *
     * @var Store
     */
    protected $store;

    /**
     * The total count of the collection,
     * Acts as if no offsets or limits were originally applied to the Model set.
     *
     * @var int
     */
    protected $totalCount;

    /**
     * Constructor.
     *
     * @param   Store           $store
     * @param   AbstractModel[] $models
     * @param   int             $totalCount
     */
    public function __construct(Store $store, array $models = [], $totalCount)
    {
        $this->pos = 0;
        $this->store = $store;
        $this->setModels($models);
        $this->totalCount = (Integer) $totalCount;
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
        $this->modelKeyMap = [];
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
        $key = $this->modelKeyMap[$this->pos];
        return $this->models[$key];
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
     * Gets the 'total' model count, as if a limit and offset were not applied.
     *
     * @return  int
     */
    public function getTotalCount()
    {
        return $this->totalCount;
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
        $key = $model->getCompositeKey();
        return isset($this->models[$key]);
    }

    /**
     * Determines if any models in this collection are dirty (have changes).
     *
     * @return  bool
     */
    public function hasDirtyModels()
    {
        foreach ($this->models as $model) {
            if (true === $model->isDirty()) {
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
        return isset($this->modelKeyMap[$this->pos]);
    }

    /**
     * Determines if the model is scheduled for addition to the collection.
     *
     * @param   AbstractModel   $model  The model to check.
     * @return  bool
     */
    public function willAdd(AbstractModel $model)
    {
        $key = $model->getCompositeKey();
        return isset($this->added[$key]);
    }

    /**
     * Determines if the model is scheduled for removal from the collection.
     *
     * @param   AbstractModel   $model  The model to check.
     * @return  bool
     */
    public function willRemove(AbstractModel $model)
    {
        $key = $model->getCompositeKey();
        return isset($this->removed[$key]);
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

        $key = $model->getCompositeKey();
        $this->models[$key] = $model;
        $this->modelKeyMap[] = $key;

        if (false === $this->hasOriginal($model)) {
            $this->original[$key] = $model;
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
        $key = $model->getCompositeKey();
        if (isset($this->{$property})) {
            unset($this->{$property}[$key]);
        }

        if ('models' === $property) {
            $keys = array_flip($this->modelKeyMap);
            if (isset($keys[$key])) {
                unset($keys[$key]);
                $this->modelKeyMap = array_keys($keys);
                $this->totalCount--;
            }
        }
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
        $key = $model->getCompositeKey();
        return isset($this->original[$key]);
    }

    /**
     * Sets a model to a collection property (original, added, removed, models).
     *
     * @param   string          $property   The property key
     * @param   AbstractModel   $model      The model to set.
     * @return  self
     */
    protected function set($property, AbstractModel $model)
    {
        $key = $model->getCompositeKey();
        $this->{$property}[$key] = $model;

        if ('models' === $property) {
            $keys = array_flip($this->modelKeyMap);
            if (!isset($keys[$key])) {
                $this->modelKeyMap[] = $key;
                $this->totalCount++;
            }
        }
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
