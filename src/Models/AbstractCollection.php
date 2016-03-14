<?php

namespace As3\Modlr\Models;

use As3\Modlr\Store\Store;
use As3\Modlr\Metadata\EntityMetadata;
use \Iterator;
use \Countable;

/**
 * Model collection that contains record representations from a persistence (database) layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class AbstractCollection implements Iterator, Countable
{
    /**
     * Current models assigned to this collection.
     * Needed for iteration, access, and count purposes.
     *
     * @var Model[]
     */
    protected $models = [];

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
    protected $store;

    /**
     * The array position.
     *
     * @var int
     */
    protected $pos = 0;

    /**
     * Whether the collection has been loaded with data from the persistence layer
     *
     * @var bool
     */
    protected $loaded = true;

    /**
     * Constructor.
     *
     * @param   EntityMetadata  $metadata
     * @param   Store           $store
     * @param   Models[]        $models
     */
    public function __construct(EntityMetadata $metadata, Store $store, array $models = [])
    {
        $this->pos = 0;
        $this->metadata = $metadata;
        $this->store = $store;
        $this->setModels($models);
    }

    /**
     * Determines if the collection is dirty.
     *
     * @return  bool
     */
    abstract public function isDirty();

    /**
     * Gets the query field for this collection.
     *
     * @return  bool
     */
    abstract public function getQueryField();

    /**
     * Gets the identifiers for this collection.
     *
     * @param   bool    $onlyUnloaded   Whether to only include unloaded models in the results.
     * @return  array
     */
    abstract public function getIdentifiers($onlyUnloaded = true);

    /**
     * Gets the model collection type.
     *
     * @return  string
     */
    public function getType()
    {
        return $this->getMetadata()->type;
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
     * Adds an model to this collection.
     * Is used during initial collection construction.
     *
     * @param   Model   $model
     * @return  self
     */
    protected function add(Model $model)
    {
        if (true === $this->has($model)) {
            return $this;
        }
        $this->validateAdd($model);
        if (true === $model->getState()->is('empty')) {
            $this->loaded = false;
        }
        $this->models[] = $model;
        return $this;
    }

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
     * @param   Model   $model  The model to validate.
     */
    protected function validateAdd(Model $model)
    {
        $this->store->validateRelationshipSet($this->getMetadata(), $model->getType());
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
     * Loads this collection from the store.
     */
    protected function loadFromStore()
    {
        if (false === $this->isLoaded()) {
            // Loads collection from the database on iteration.
            $models = $this->store->loadCollection($this);
            $this->setModels($models);
            $this->loaded = true;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        $this->loadFromStore();
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
