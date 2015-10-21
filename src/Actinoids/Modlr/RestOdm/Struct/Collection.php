<?php

namespace Actinoids\Modlr\RestOdm\Struct;

use \Iterator;
use \ArrayAccess;
use \Countable;

/**
 * Collection object that contains multiple identifier documents.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Collection implements Iterator, ArrayAccess, Countable
{
    /**
     * Resources assign to this collection.
     *
     * @var EntityInterface[]
     */
    protected $entities = [];

    /**
     * The array position.
     *
     * @var int
     */
    protected $pos = 0;

    /**
     * Constructor.
     *
     * @param   string  $type
     */
    public function __construct()
    {
        $this->pos = 0;
    }

    /**
     * Gets a unique list of all entity types assigned to this collection.
     *
     * @return  array
     */
    public function getResourceTypes()
    {
        $types = [];
        foreach ($this as $entity) {
            $types[] = $entity->getType();
        }
        return array_unique($types);
    }

    /**
     * Adds an identifier or entity to this collection.
     *
     * @param   EntityInterface   $entity
     * @return  self
     */
    public function add(EntityInterface $entity)
    {
        $this->entities[] = $entity;
        return $this;
    }

    /**
     * Returns all identifiers/entities in this collection.
     *
     * @return  EntityInterface[]
     */
    public function all()
    {
        return $this->entities;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->entities);
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
        return $this->entities[$this->pos];
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
        return isset($this->entities[$this->pos]);
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
        return isset($this->entities[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->entities[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return isset($this->entities[$offset]) ? $this->entities[$offset] : null;
    }
}
