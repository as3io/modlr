<?php

namespace As3\Modlr\Store;

use As3\Modlr\Models\Model;

/**
 * Manages Model objects that are currently loaded into memory.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Cache
{
    /**
     * All models currently loaded in memory.
     * Is an associative array of model type keys to model objects.
     *
     * @var array
     */
    private $models = [];

    /**
     * Gets all models in the memory cache.
     * Will be keyed by model type.
     *
     * @return  array
     */
    public function getAll()
    {
        return $this->models;
    }

    /**
     * Gets all models in the memory cache for a specific type.
     *
     * @param   string  $typeKey
     * @return  Model[]
     */
    public function getAllForType($typeKey)
    {
        if (isset($this->models[$typeKey])) {
            return $this->models[$typeKey];
        }
        return [];
    }

    /**
     * Clears all models in the memory cache for a specific type.
     *
     * @param   string  $typeKey
     * @return  self
     */
    public function clearType($typeKey)
    {
        if (isset($this->models[$typeKey])) {
            unset($this->models[$typeKey]);
        }
        return $this;
    }

    /**
     * Clears all models in the memory cache.
     *
     * @return  self
     */
    public function clearAll()
    {
        $this->models = [];
        return $this;
    }

    /**
     * Pushes a model into the memory cache.
     *
     * @param   Model   $model
     * @return  self
     */
    public function push(Model $model)
    {
        $this->models[$model->getType()][$model->getId()] = $model;
        return $this;
    }

    /**
     * Removes a model from the memory cache, based on type and identifier.
     *
     * @param   string  $typeKey
     * @param   string  $identifier
     * @return  self
     */
    public function remove($typeKey, $identifier)
    {
        if (isset($this->models[$typeKey][$identifier])) {
            unset($this->models[$typeKey][$identifier]);
        }
        return $this;
    }

    /**
     * Gets a model from the memory cache, based on type and identifier.
     *
     * @param   string  $typeKey
     * @param   string  $identifier
     * @return  Model|null
     */
    public function get($typeKey, $identifier)
    {
        $map = $this->getAllForType($typeKey);
        if (isset($map[$identifier])) {
            return $map[$identifier];
        }
        return null;
    }

    /**
     * Determines if a model exists in the memory cache, based on type and identifier.
     *
     * @param   string  $typeKey
     * @param   string  $identifier
     * @return  bool
     */
    public function has($typeKey, $identifier)
    {
        return null !== $this->get($typeKey, $identifier);
    }
}
