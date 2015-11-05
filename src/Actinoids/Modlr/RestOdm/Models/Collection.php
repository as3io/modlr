<?php

namespace Actinoids\Modlr\RestOdm\Models;

use Actinoids\Modlr\RestOdm\Store\Store;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use \Iterator;
use \Countable;

/**
 * Model collection that contains record representations from a persistence (database) layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Collection implements Iterator, Countable
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

    /**
     * Gets the model collection type.
     *
     * @return  string
     */
    public function getType()
    {
        return $this->metadata->type;
    }

    /**
     * Gets the metadata for the model collection.
     *
     * @return  EntityMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Gets all the identifiers of all models in this collection.
     *
     * @param   bool    $onlyEmpty  Flags whether to only include empty (non-loaded) model ids.
     * @return  array
     */
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
     * Validates that the collection supports the incoming model.
     *
     * @param   Model   $model  The model to validate.
     */
    protected function validateAdd(Model $model)
    {
        $this->store->validateRelationshipSet($this->getMetadata(), $model->getType());
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
     * Determines if the Model is included in the collection.
     *
     * @param   Model   $model  The model to check.
     * @return  bool
     */
    public function has(Model $model)
    {
        return -1 !== $this->indexOf('models', $model);
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
     * Gets the Model array index from a collection property (original, added, removed, models).
     * Will return -1 if the model was not found.
     *
     * @param   string  $property   The property key
     * @param   Model   $model      The model to check.
     * @return  int
     */
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
     * Determines if this collection is dirty (has changes).
     *
     * @return  bool
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
}
