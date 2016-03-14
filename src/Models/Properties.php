<?php

namespace As3\Modlr\Models;

use As3\Modlr\Store\Store;
use As3\Modlr\Metadata\EntityMetadata;

/**
 * Represents the properties (atttributes and relationships) of a Model.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class Properties
{
    /**
     * The original property values.
     *
     * @var array
     */
    private $original = [];

    /**
     * The current/modified property values.
     *
     * @var array
     */
    private $current = [];

    /**
     * Any properties that have been flagged for removal.
     *
     * @var array
     */
    private $remove = [];

    /**
     * Constructor.
     *
     * @param   array   $original   Any original properties to apply.
     */
    public function __construct(array $original = [])
    {
        $this->original = $original;
    }

    /**
     * Gets the current value of an property.
     *
     * @param   string  $key    The property key.
     * @return  mixed
     */
    public function get($key)
    {
        if ($this->willRemove($key)) {
            return null;
        }
        if (true === $this->willChange($key)) {
            return $this->getCurrent($key);
        }
        return $this->getOriginal($key);
    }

    /**
     * Sets a new value to an property.
     *
     * @param   string  $key    The property key.
     * @param   mixed   $value  The value to set.
     * @return  mixed
     */
    public function set($key, $value)
    {
        if (null === $value) {
            return $this->remove($key);
        }
        $this->clearRemoval($key);

        if ($value === $this->getOriginal($key)) {
            $this->clearChange($key);
        } else {
            $this->current[$key] = $value;
        }
        return $this;
    }

    /**
     * Sets a new value to an property.
     *
     * @param   string  $key    The property key.
     * @return  self
     */
    public function remove($key)
    {
        if (false === $this->willRemove($key)) {
            $this->clearChange($key);
            if (true === $this->hasOriginal($key)) {
                $this->remove[] = $key;
            }
        }
        return $this;
    }

    /**
     * Rolls back the properties to their original state.
     *
     * @return  self
     */
    public function rollback()
    {
        $this->current = [];
        $this->remove = [];
        return $this;
    }

    /**
     * Replaces the current properties with new ones.
     * Will revert/rollback any current changes.
     *
     * @param   array   $original
     * @return  self
     */
    public function replace(array $original)
    {
        $this->rollback();
        $this->original = $original;
        return $this;
    }


    /**
     * Deteremines if the properties have different values from their original state.
     *
     * @return  bool
     */
    public function areDirty()
    {
        return !empty($this->current) || !empty($this->remove);
    }

    /**
     * Calculates any property changes.
     *
     * @return  array
     */
    public function calculateChangeSet()
    {
        $set = [];
        foreach ($this->current as $key => $current) {
            $original = isset($this->original[$key]) ? $this->original[$key] : null;
            $set[$key]['old'] = $original;
            $set[$key]['new'] = $current;
        }
        foreach ($this->remove as $key) {
            $set[$key]['old'] = $this->original[$key];
            $set[$key]['new'] = null;
        }
        ksort($set);
        return $set;
    }

    /**
     * Clears an property from the removal queue.
     *
     * @param   string  $key    The field key.
     * @return  self
     */
    protected function clearRemoval($key)
    {
        if (false === $this->willRemove($key)) {
            return $this;
        }
        $key = array_search($key, $this->remove);
        unset($this->remove[$key]);
        $this->remove = array_values($this->remove);
        return $this;
    }

    /**
     * Clears an property as having been changed.
     *
     * @param   string  $key    The field key.
     * @return  self
     */
    protected function clearChange($key)
    {
        if (true === $this->willChange($key)) {
            unset($this->current[$key]);
        }
        return $this;
    }

    /**
     * Determines if an property is in the removal queue.
     *
     * @param   string  $key    The field key.
     * @return  bool
     */
    protected function willRemove($key)
    {
        return in_array($key, $this->remove);
    }

    /**
     * Determines if an property has a new value.
     *
     * @param   string  $key    The field key.
     * @return  bool
     */
    protected function willChange($key)
    {
        return null !== $this->getCurrent($key);
    }

    /**
     * Determines if an property has an original value.
     *
     * @param   string  $key    The field key.
     * @return  bool
     */
    protected function hasOriginal($key)
    {
        return null !== $this->getOriginal($key);
    }

    /**
     * Gets the property's original value.
     *
     * @param   string  $key    The field key.
     * @return  mixed
     */
    protected function getOriginal($key)
    {
        if (isset($this->original[$key])) {
            return $this->original[$key];
        }
        return null;
    }

    /**
     * Gets all original properties.
     *
     * @return  array
     */
    protected function getOriginalAll()
    {
        return $this->original;
    }

    /**
     * Gets all current properties.
     *
     * @return  array
     */
    protected function getCurrentAll()
    {
        return $this->current;
    }

    /**
     * Gets the property's current value.
     *
     * @param   string  $key    The field key.
     * @return  mixed
     */
    protected function getCurrent($key)
    {
        if (isset($this->current[$key])) {
            return $this->current[$key];
        }
        return null;
    }
}
