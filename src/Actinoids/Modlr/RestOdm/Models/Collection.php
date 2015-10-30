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
     * The EntityMetadata that 'owns' this collection.
     *
     * @var EntityMetadata
     */
    private $metadata;

    /**
     * Models assigned to this collection.
     *
     * @var Model[]
     */
    private $models = [];

    /**
     * The array position.
     *
     * @var int
     */
    private $pos = 0;

    public function __construct(EntityMetadata $metadata)
    {
        $this->pos = 0;
        $this->metadata = $metadata;
    }

    /**
     * Adds an model to this collection.
     *
     * @todo    Validate that incomining models are supported by this collection.
     * @param   Model   $model
     * @return  self
     */
    public function add(Model $model)
    {
        $this->models[] = $model;
        return $this;
    }

    /**
     * Returns all models in this collection.
     *
     * @return  Model[]
     */
    public function all()
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
