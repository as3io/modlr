<?php

namespace Actinoids\Modlr\RestOdm\Models;

use Actinoids\Modlr\RestOdm\Store\Store;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use \Iterator;
use \ArrayAccess;
use \Countable;

/**
 * Model collection that contains record representations from a persistence (database) layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Collection implements Iterator, ArrayAccess, Countable
{
    /**
     * Original models assigned to this collection.
     *
     * @var Model[]
     */
    private $original = [];

    /**
     * Current models assigned to this collection.
     * Needed for iteration, access, and count purposes.
     *
     * @var Model[]
     */
    private $models = [];

    /**
     * Models added to this collection.
     * Tracks newly added models for rollback/change purposes.
     *
     * @var Model[]
     */
    private $added = [];

    /**
     * Models removed from this collection.
     * Tracks removed models for rollback/change purposes.
     *
     * @var Model[]
     */
    private $removed = [];

    /**
     * The EntityMetadata that 'owns' this collection.
     *
     * @var EntityMetadata
     */
    private $metadata;

    /**
     * The store for handling storage operations.
     *
     * @var Store
     */
    private $store;

    /**
     * The array position.
     *
     * @var int
     */
    private $pos = 0;

    /**
     * Whether the collection has been loaded with data from the persistence layer
     *
     * @var bool
     */
    private $loaded = true;

    public function __construct(EntityMetadata $metadata, Store $store, array $models = [])
    {
        $this->pos = 0;
        $this->metadata = $metadata;
        $this->store = $store;
        foreach ($models as $model) {
            $this->add($model);
        }
    }

    public function getType()
    {
        return $this->metadata->type;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function getIdentifiers($onlyEmpty = true)
    {
        $identifiers = [];
        foreach ($this->models as $model) {
            if (true === $onlyEmpty && true === $model->getState()->is('empty')) {
                $identifiers[] = $model->getId();
            }
        }
        return $identifiers;
    }

    /**
     * Adds an model to this collection.
     * Is used during initial collection construction.
     *
     * @todo    Validate that incomining models are supported by this collection.
     * @param   Model   $model
     * @return  self
     */
    protected function add(Model $model)
    {
        $this->validateAdd($model);
        if (true === $model->getState()->is('empty')) {
            $this->loaded = false;
        }
        $this->original[] = $model;
        $this->models[] = $model;
        return $this;
    }

    public function rollback()
    {
        $this->models = $this->original;
        $this->added = [];
        $this->removed = [];
        return $this;
    }

    public function clear()
    {
        $this->models = [];
        $this->added = [];
        $this->removed = $this->original;
    }

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
        $this->set('models', $model);
        $this->set('added', $model);
        return $this;
    }

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

        $this->evict('models', $model);
        $this->set('removed', $model);
        return $this;
    }

    protected function validateAdd(Model $model)
    {
        if (false === $this->canAdd($model)) {
            throw new \InvalidArgumentException(sprintf('The model type "%s" cannot be added to this collection, as it is not supported.'));
        }
    }

    public function canAdd(Model $model)
    {
        $metadata = $this->getMetadata();
        if (true === $metadata->isPolymorphic()) {
            return in_array($model->getType(), $metadata->ownedTypes);
        }
        return $metadata->type === $model->getType();
    }

    public function willRemove(Model $model)
    {
        return -1 !== $this->indexOf('removed', $model);
    }

    public function willAdd(Model $model)
    {
        return -1 !== $this->indexOf('added', $model);
    }

    public function has(Model $model)
    {
        return -1 !== $this->indexOf('models', $model);
    }

    protected function hasOriginal(Model $model)
    {
        return -1 !== $this->indexOf('original', $model);
    }

    protected function set($property, Model $model)
    {
        $models = $this->$property;
        $models[] = $model;
        $this->$property = $models;
        return $this;
    }

    protected function evict($property, Model $model)
    {
        $index = $this->indexOf($property, $model);
        $models = $this->$property;
        unset($models[$index]);
        $this->$property = array_values($models);
        return $this;
    }

    protected function indexOf($property, Model $model)
    {
        // @todo For performance, can we create a map using the model's composite key to avoid these loops?
        foreach ($this->$property as $index => $loaded) {
            if ($model->getType() === $loaded->getType() && $model->getId() === $loaded->getId()) {
                return $index;
            }
        }
        return -1;
    }

    public function isLoaded()
    {
        return $this->loaded;
    }

    public function hasDirtyModels()
    {
        foreach ($this->models as $model) {
            if (true === $model->getState()->is('dirty')) {
                return true;
            }
        }
        return false;
    }

    public function isDirty()
    {
        return !empty($this->added) || !empty($this->removed);
    }

    /**
     * Returns all models in this collection without triggering auto-loading.
     *
     * @return  Model[]
     */
    public function allWithoutLoad()
    {
        return $this->models;
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
    public function rewind()
    {
        if (false === $this->isLoaded()) {
            // Loads collection from the database on iteration.
            $this->store->loadCollection($this, $this->models);
            $this->loaded = true;
        }
        $this->pos = 0;
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->models[$this->pos];
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
     * {@inheritDoc}
     */
    public function valid()
    {
        return isset($this->models[$this->pos]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->add($value);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->models[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->models[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return isset($this->models[$offset]) ? $this->models[$offset] : null;
    }
}
