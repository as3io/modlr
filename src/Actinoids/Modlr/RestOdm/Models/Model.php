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
     * The model state.
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
     * @todo    Currently, if properties are applied on construct for a new model, they will not be reflected in the changeset.
     * @param   EntityMetadata  $metadata   The internal entity metadata that supports this Model.
     * @param   string          $identifier The database identifier.
     * @param   Store           $store      The model store service for handling persistence operations.
     * @param   array           $properties The model's attributes and relationships as a flattened, keyed array.
     */
    public function __construct(EntityMetadata $metadata, $identifier, Store $store, array $properties = [])
    {
        $this->metadata = $metadata;
        $this->identifier = $identifier;
        $this->store = $store;
        $this->state = new State();
        $this->initialize($properties);
    }

    /**
     * Sets an attribute value.
     * Will do a dirty check immediately after setting.
     *
     * @todo    Handle data type conversion. Should this happen here?
     * @param   string  $key    The attribute key (field) name.
     * @param   mixed   $value  The value to apply.
     * @return  self
     */
    public function attribute($key, $value)
    {
        if (false === $this->getMetadata()->hasAttribute($key)) {
            return $this;
        }
        $this->attributes->set($key, $value);
        $this->state->setDirty($this->isDirty());
        return $this;
    }

    public function getAttribute($key)
    {
        if (false === $this->getMetadata()->hasAttribute($key)) {
            return null;
        }
        return $this->attributes->get($key);
    }

    /**
     * Saves the model.
     *
     * @return  self
     */
    public function save()
    {
        if (true === $this->getState()->is('deleted')) {
            return $this;
        }
        $this->store->commit($this);
        return $this;
    }

    /**
     * Rolls back a model to its original, database values.
     *
     * @todo    Implement relationship rollbacks.
     * @return  self
     */
    public function rollback()
    {
        $this->attributes->rollback();
        return $this;
    }

    /**
     * Reloads the model from the database.
     *
     * @return  self
     */
    public function reload()
    {
        if (true === $this->getState()->is('deleted')) {
            return $this;
        }
        $record = $this->store->retrieveRecord($this->getType(), $this->getId());
        $this->initialize($record->getProperties());
        return $this;
    }

    /**
     * Restores an in-memory deleted object back to the database.
     *
     * @todo    Implement if needed. Or should restore clear a pending delete?
     * @return  self
     */
    public function restore()
    {
        return $this;
    }

    /**
     * Marks the record for deletion.
     * Will not remove from the database until $this->save() is called.
     *
     * @return  self
     * @throws  \RuntimeException   If a new (unsaved) model is deleted.
     */
    public function delete()
    {
        if (true === $this->getState()->is('new')) {
            throw new \RuntimeException('You cannot delete a new model');
        }
        if (true === $this->getState()->is('deleted')) {
            return $this;
        }
        $this->getState()->setDeleting();
        return $this;
    }

    /**
     * Initializes the modal and loads it's attributes and relationships.
     *
     * @todo    Add relationship support.
     * @param   array   $properties     The record attributes and relationships to apply.
     * @return  self
     */
    protected function initialize(array $properties)
    {
        $meta = $this->getMetadata();
        $attributes = [];
        foreach ($properties as $key => $value) {
            if (false === $meta->hasAttribute($key)) {
                continue;
            }
            $attributes[$key] = $value;
        }
        $this->attributes = new Attributes($attributes);
        $this->state->setDirty($this->isDirty());
        return $this;
    }

    /**
     * Determines if the model is currently dirty.
     * Checks against the attribute and relationship dirty states.
     *
     * @todo    Implement relationships.
     * @return  bool
     */
    public function isDirty()
    {
        return $this->attributes->areDirty();
    }

    /**
     * Gets the current change set of attributes and relationships.
     *
     * @todo    Implement relationship changeset.
     * @return  array
     */
    public function getChangeSet()
    {
        return $this->attributes->calculateChangeSet();
    }

    /**
     * Gets the model state object.
     *
     * @todo    Should this be public? State setting should likely be locked from the outside world.
     * @return  State
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Gets the metadata for this model.
     *
     * @return  EntityMetadata
     */
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
