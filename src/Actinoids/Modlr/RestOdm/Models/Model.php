<?php

namespace Actinoids\Modlr\RestOdm\Models;

use Actinoids\Modlr\RestOdm\Models\Relationships;
use Actinoids\Modlr\RestOdm\Persister\Record;
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
     * The Model's has-one relationships
     *
     * @var Relationships\HasOne
     */
    protected $hasOneRelationships;

    /**
     * The Model's has-many relationships
     *
     * @var Relationships\HasMany
     */
    protected $hasManyRelationships;

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
     * @todo    Currently, if properties are applied on construct for a new model (new record), they will not be reflected in the changeset.
     * @param   EntityMetadata  $metadata   The internal entity metadata that supports this Model.
     * @param   string          $identifier The database identifier.
     * @param   Store           $store      The model store service for handling persistence operations.
     * @param   Record          $record     The model's attributes and relationships from the db layer to init the model with.
     */
    public function __construct(EntityMetadata $metadata, $identifier, Store $store, Record $record = null)
    {
        $this->metadata = $metadata;
        $this->identifier = $identifier;
        $this->store = $store;
        $this->state = new State();
        $this->initialize($record);
    }

    /**
     * Gets a model property: an attribute value, a has-one model, or a has-many model collection.
     * Returns null if the property does not exist on the model or is not set.
     * Is a proxy for @see getAttribute($key) and getRelationship($key)
     *
     * @param   string  $key    The property field key.
     * @return  Model|Collection|null|mixed
     */
    public function get($key)
    {
        if (true === $this->getMetadata()->hasAttribute($key)) {
            return $this->getAttribute($key);
        }
        return $this->getRelationship($key);
    }

    /**
     * Gets an attribute value.
     *
     * @param   string  $key    The attribute key (field) name.
     * @return  mixed
     */
    protected function getAttribute($key)
    {
        $this->touch();
        return $this->attributes->get($key);
    }

    /**
     * Gets a relationship value.
     *
     * @param   string  $key    The relationship key (field) name.
     * @return  Model|Collection|null
     */
    protected function getRelationship($key)
    {
        $relMeta = $this->getMetadata()->getRelationship($key);
        if (null === $relMeta) {
            return null;
        }
        $this->touch();
        if (true === $relMeta->isOne()) {
            return $this->hasOneRelationships->get($key);
        }
        return $this->hasManyRelationships->get($key);
    }

    /**
     * Pushes a Model into a has-many relationship collection.
     * This method must be used for has-many relationships. Set will not work, as it expects an entire Collection.
     *
     * @param   string  $key
     * @param   Model   $model
     * @return  self
     */
    public function push($key, Model $model)
    {
        $relMeta = $this->getMetadata()->getRelationship($key);
        if (null === $relMeta) {
            return $this;
        }
        if (true === $relMeta->isOne()) {
            return $this->setHasOne($key, $model);
        }

        $collection = $this->hasManyRelationships->get($key);
        if (null === $collection) {
            $collection = new Collection($this->getMetadata(), $this->store);
            $this->setHasMany($key, $collection);
        } else {
            $this->touch();
        }
        $collection->push($model);
        $this->state->setDirty($this->isDirty());
        return $this;
    }

    /**
     * Sets a model property: an attribute value, a has-one model, or an entire has-many model collection.
     * Note: To push a single Model into a has-many collection, use @see push() instead.
     * Is a proxy for @see setAttribute() and setRelationship()
     *
     * @param   string  $key                The property field key.
     * @param   Model|Collection|null|mixed The value to set.
     * @return  self.
     */
    public function set($key, $value)
    {
        if (true === $this->getMetadata()->hasAttribute($key)) {
            return $this->setAttribute($key, $value);
        }
        return $this->setRelationship($key, $value);
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
    protected function setAttribute($key, $value)
    {
        $this->touch();
        $this->attributes->set($key, $value);
        $this->state->setDirty($this->isDirty());
        return $this;
    }

    /**
     * Sets a relationship value.
     *
     * @param   string                  $key
     * @param   Model|Collection|null   $value
     * @return  self
     */
    protected function setRelationship($key, $value)
    {
        $relMeta = $this->getMetadata()->getRelationship($key);
        if (null === $relMeta) {
            return $this;
        }
        if (true === $relMeta->isOne()) {
            return $this->setHasOne($key, $value);
        }
        return $this->setHasMany($key, $value);
    }

    /**
     * Sets a has-one relationship.
     *
     * @todo    Validate the the model can be set, based on metadata.
     * @param   string      $key    The relationship key (field) name.
     * @param   Model|null  $model  The model to relate.
     * @return  self
     */
    protected function setHasOne($key, Model $model = null)
    {
        $this->touch();
        $this->hasOneRelationships->set($key, $model);
        $this->state->setDirty($this->isDirty());
        return $this;
    }

    /**
     * Sets/replaces an entire has-many relationship Collection.
     *
     * @todo    Validate the the collection can be set, based on metadata.
     * @param   string      $key        The relationship key (field) name.
     * @param   Collection  $collection The model to set/replace.
     * @return  self
     */
    protected function setHasMany($key, Collection $collection)
    {
        $this->touch();
        $this->hasManyRelationships->set($key, $collection);
        $this->state->setDirty($this->isDirty());
        return $this;
    }

    /**
     * Saves the model.
     *
     * @param   Implement cascade relationship saves. Or should the store handle this?
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
     * @todo    Implement relationship rollbacks, especually has-many collection rollbacks.
     * @return  self
     */
    public function rollback()
    {
        $this->attributes->rollback();
        $this->hasOneRelationships->rollback();
        $this->hasManyRelationships->rollback();
        return $this;
    }

    /**
     * Reloads the model from the database.
     *
     * @return  self
     */
    public function reload()
    {
        return $this->touch(true);
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
     * Touches the model.
     * If the model is currently empty, it will query the database and fill/load the model.
     *
     * @param   bool    $force  Whether to force the load, even if the model is currently loaded.
     * @return  self
     */
    protected function touch($force = false)
    {
        if (true === $this->getState()->is('deleted')) {
            return $this;
        }
        if (true === $this->getState()->is('empty') || true === $force) {
            $record = $this->store->retrieveRecord($this->getType(), $this->getId());
            $this->initialize($record);
        }
        $this->state->setLoaded();
        return $this;
    }

    /**
     * Initializes the model and loads its attributes and relationships.
     *
     * @todo    Made public so collections can initialize models. Not sure if we want this??
     * @todo    Add relationship support.
     * @param   Record   $record     The db attributes and relationships to apply.
     * @return  self
     */
    public function initialize(Record $record = null)
    {
        if (null === $record) {
            $this->attributes = new Attributes();
            $this->hasOneRelationships  = new Relationships\HasOne();
            $this->hasManyRelationships = new Relationships\HasMany();
            // @todo Is this necessary? They're empty.
            $this->state->setDirty($this->isDirty());
            return $this;
        }

        $meta = $this->getMetadata();
        $hasOne = [];
        $hasMany = [];
        $attributes = [];
        foreach ($record->getProperties() as $key => $value) {
            if (true === $meta->hasAttribute($key)) {
                $attributes[$key] = $value;
                continue;
            }
            if (true === $meta->hasRelationship($key)) {
                $relMeta = $meta->getRelationship($key);
                if (true === $relMeta->isOne()) {
                    $hasOne[$key] = $this->store->loadHasOne($value['type'], $value['id']);
                } else {
                    $hasMany[$key] = $this->store->loadHasMany($relMeta->getEntityType(), $value);
                }
            }

        }
        $this->attributes           = new Attributes($attributes);
        $this->hasOneRelationships  = new Relationships\HasOne($hasOne);
        $this->hasManyRelationships = new Relationships\HasMany($hasMany);
        $this->state->setDirty($this->isDirty());
        return $this;
    }

    /**
     * Determines if the model is currently dirty.
     * Checks against the attribute and relationship dirty states.
     * @return  bool
     */
    public function isDirty()
    {
        return true === $this->attributes->areDirty()
            || true === $this->hasOneRelationships->areDirty()
            || true === $this->hasManyRelationships->areDirty()
        ;
    }

    /**
     * Gets the current change set of attributes and relationships.
     *
     * @return  array
     */
    public function getChangeSet()
    {
        return [
            'attributes'    => $this->attributes->calculateChangeSet(),
            'hasOne'        => $this->hasOneRelationships->calculateChangeSet(),
            'hasMany'       => $this->hasManyRelationships->calculateChangeSet(),
        ];
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
