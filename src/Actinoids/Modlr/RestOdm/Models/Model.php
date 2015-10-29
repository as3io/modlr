<?php

namespace Actinoids\Modlr\RestOdm\Models;

use Actinoids\Modlr\RestOdm\Store\Store;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;

/**
 * Represents a data record from a persistence (database) layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Model
{
    /**
     * The id value of this model.
     * Always converted to a string when in the model context.
     *
     * @var string
     */
    protected $identifier;

    /**
     * The Model's attributes
     *
     * @var Attributes
     */
    protected $attributes;

    /**
     * The model state, with default values.
     *
     * @var State
     */
    protected $state;

    /**
     * The EntityMetadata that defines this Model.
     *
     * @var EntityMetadata
     */
    protected $metadata;

    /**
     * The Model Store for handling lifecycle operations.
     *
     * @var Store
     */
    protected $store;

    /**
     * Constructor.
     *
     * @param   EntityMetadata  $metadata   The internal entity metadata that supports this Model.
     * @param   string          $identifier The database identifier.
     * @param   Store           $store      The model store service for handling persistence operations.
     * @param   array           $properties The model's attributes and relationships as a flattened, keyed array.
     */
    public function __construct(EntityMetadata $metadata, $identifier, Store $store, array $data = [])
    {
        $this->metadata = $metadata;
        $this->identifier = $identifier;
        $this->store = $store;
        $this->state = new State();
        $this->initialize($data);
    }

    public function isDirty()
    {
        return $this->attributes->areDirty();
    }

    public function setAttribute($key, $value)
    {
        if (false === $this->getMetadata()->hasAttribute($key)) {
            return $this;
        }
        $this->attributes->set($key, $value);
        return $this;
    }


    /**
     * @todo    This likely should be public. Anyone could set the state.
     */
    public function getState()
    {
        return $this->state;
    }

    protected function initialize(array $data)
    {
        $meta = $this->getMetadata();
        $attributes = [];
        foreach ($data as $key => $value) {
            if (false === $meta->hasAttribute($key)) {
                continue;
            }
            $attributes[$key] = $value;
        }
        $this->attributes = new Attributes($attributes);
        return $this;
    }

    public function setState($state, $bit = true)
    {
        if (!isset($this->state[$state])) {
            throw new \RuntimeException(sprintf('The state "%s" is not valid.'));
        }
        $this->state[$state] = (Boolean) $bit;
        return $this;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->identifier;
    }

     /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return $this->metadata->type;
    }

    /**
     * {@inheritDoc}
     */
    public function getCompositeKey()
    {
        return sprintf('%s.%s', $this->getType(), $this->getId());
    }
}
