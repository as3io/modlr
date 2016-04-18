<?php

namespace As3\Modlr\Models\Collections;

use As3\Modlr\Metadata\EntityMetadata;
use As3\Modlr\Models\AbstractModel;
use As3\Modlr\Store\Store;

/**
 * Model collection that contains record representations from a persistence (database) layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class ModelCollection extends AbstractCollection
{
    /**
     * @var EntityMetadata
     */
    protected $metadata;

    /**
     * Constructor.
     *
     * @param   EntityMetadata  $metadata
     * @param   Store           $store
     * @param   AbstractModel[] $models
     */
    public function __construct(EntityMetadata $metadata, Store $store, array $models = [])
    {
        $this->metadata = $metadata;
        parent::__construct($store, $models);
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
     * Gets the identifiers for this collection.
     *
     * @param   bool    $onlyUnloaded   Whether to only include unloaded models in the results.
     * @return  array
     */
    abstract public function getIdentifiers($onlyUnloaded = true);

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
     * Gets the query field for this collection.
     *
     * @return  bool
     */
    abstract public function getQueryField();

    /**
     * {@inheritdoc}
     *
     * Overloaded to ensure models are loaded from the store.
     *
     */
    public function getSingleResult()
    {
        $this->loadFromStore();
        return parent::getSingleResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->getMetadata()->type;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        $this->loadFromStore();
        parent::rewind();
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
     * {@inheritdoc}
     */
    protected function modelsMatch(AbstractModel $model, AbstractModel $loaded)
    {
        $this->validateModelClass($model);
        $this->validateModelClass($loaded);
        return $model->getType() === $loaded->getType() && $model->getId() === $loaded->getId();
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAdd(AbstractModel $model)
    {
        $this->validateModelClass($model);
        $this->store->validateRelationshipSet($this->getMetadata(), $model->getType());
    }

    /**
     * Validates that the model class instance is supported.
     *
     * @param   AbstractModel   $model
     * @throws  \InvalidArgumentException
     */
    protected function validateModelClass(AbstractModel $model)
    {
        if (!$model instanceof Model) {
            throw new \InvalidArgumentExcepton('The model must be an instanceof of Model');
        }
    }
}
