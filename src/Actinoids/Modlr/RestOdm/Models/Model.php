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
     * Enables/disables collection auto-initialization on iteration.
     * Will not load/fill the collection from the database if false.
     * Is useful for large hasMany iterations where only id and type are required (ala serialization).
     *
     * @var bool
     */
    protected $collectionAutoInit = true;

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
     * @param   EntityMetadata  $metadata   The internal entity metadata that supports this Model.
     * @param   string          $identifier The database identifier.
     * @param   Store           $store      The model store service for handling persistence operations.
     * @param   Record|null     $record     The model's attributes and relationships from the db layer to init the model with. New models will constructed with a null record.
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
     * Gets the unique identifier of this model.
     *
     * @api
     * @return  string
     */
    public function getId()
    {
        return $this->identifier;
    }

    /**
     * Gets the model type.
     *
     * @api
     * @return  string
     */
    public function getType()
    {
        return $this->metadata->type;
    }

    /**
     * Gets the composite key of the model by combining the model type with the unique id.
     *
     * @api
     * @return  string
     */
    public function getCompositeKey()
    {
        return sprintf('%s.%s', $this->getType(), $this->getId());
    }

    /**
     * Enables or disables has-many collection auto-initialization from the database.
     *
     * @param   bool    $bit    Whether to enable/disable.
     * @return  self
     */
    public function enableCollectionAutoInit($bit = true)
    {
        $this->collectionAutoInit = (Boolean) $bit;
        return $this;
    }

    /**
     * Gets a model property.
     * Will either be an attribute value, a has-one model, or an array representation of a has-many collection.
     * Returns null if the property does not exist on the model or is not set.
     * Is a proxy for @see getAttribute($key) and getRelationship($key)
     *
     * @api
     * @param   string  $key    The property field key.
     * @return  Model|Model[]|null|mixed
     */
    public function get($key)
    {
        if (true === $this->isAttribute($key)) {
            return $this->getAttribute($key);
        }
        return $this->getRelationship($key);
    }

    /**
     * Determines if a property key is an attribute.
     *
     * @api
     * @param   string  $key    The property key.
     * @return  bool
     */
    public function isAttribute($key)
    {
        return $this->getMetadata()->hasAttribute($key);
    }

    /**
     * Determines if an attribute key is calculated.
     *
     * @param   string  $key    The attribute key.
     * @return  bool
     */
    protected function isCalculatedAttribute($key)
    {
        if (false === $this->isAttribute($key)) {
            return false;
        }
        return $this->getMetadata()->getAttribute($key)->isCalculated();
    }

    /**
     * Gets an attribute value.
     *
     * @param   string  $key    The attribute key (field) name.
     * @return  mixed
     */
    protected function getAttribute($key)
    {
        if (true === $this->isCalculatedAttribute($key)) {
            return $this->getCalculatedAttribute($key);
        }
        $this->touch();
        return $this->attributes->get($key);
    }

    /**
     * Gets a calculated attribute value.
     *
     * @param   string  $key    The attribute key (field) name.
     * @return  mixed
     */
    protected function getCalculatedAttribute($key)
    {
        $attrMeta = $this->getMetadata()->getAttribute($key);
        $class  = $attrMeta->calculated['class'];
        $method = $attrMeta->calculated['method'];

        $value = $class::$method($this);
        return $this->convertAttributeValue($key, $value);
    }

    /**
     * Determines if a property key is a relationship (either has-one or has-many).
     *
     * @api
     * @param   string  $key    The property key.
     * @return  bool
     */
    public function isRelationship($key)
    {
        return $this->getMetadata()->hasRelationship($key);
    }

    /**
     * Determines if a property key is a an inverse relationship.
     *
     * @api
     * @param   string  $key    The property key.
     * @return  bool
     */
    public function isInverse($key)
    {
        if (false === $this->isRelationship($key)) {
            return false;
        }
        return $this->getMetadata()->getRelationship($key)->isInverse;
    }

    /**
     * Determines if a property key is a has-one relationship.
     *
     * @api
     * @param   string  $key    The property key.
     * @return  bool
     */
    public function isHasOne($key)
    {
        if (false === $this->isRelationship($key)) {
            return false;
        }
        return $this->getMetadata()->getRelationship($key)->isOne();
    }

    /**
     * Determines if a property key is a has-many relationship.
     *
     * @api
     * @param   string  $key    The property key.
     * @return  bool
     */
    public function isHasMany($key)
    {
        if (false === $this->isRelationship($key)) {
            return false;
        }
        return $this->getMetadata()->getRelationship($key)->isMany();
    }

    /**
     * Gets a relationship value.
     *
     * @param   string  $key    The relationship key (field) name.
     * @return  Model|array|null
     * @throws  \RuntimeException If hasMany relationships are accessed directly.
     */
    protected function getRelationship($key)
    {
        if (true === $this->isHasOne($key)) {
            $this->touch();
            return $this->hasOneRelationships->get($key);
        }
        if (true === $this->isHasMany($key)) {
            $this->touch();
            $collection = $this->hasManyRelationships->get($key);
            if ($collection->isLoaded($collection)) {
                return iterator_to_array($collection);
            }
            return (true === $this->collectionAutoInit) ? iterator_to_array($collection) : $collection->allWithoutLoad();
        }
        return null;
    }

    /**
     * Pushes a Model into a has-many relationship collection.
     * This method must be used for has-many relationships. Direct set is not supported.
     * To completely replace a has-many, call clear() first and then push() the new Models.
     *
     * @api
     * @param   string  $key
     * @param   Model   $model
     * @return  self
     */
    public function push($key, Model $model)
    {
        if (true === $this->isHasOne($key)) {
            return $this->setHasOne($key, $model);
        }
        if (false === $this->isHasMany($key)) {
            return $this;
        }
        if (true === $this->isInverse($key)) {
            throw ModelException::cannotModifyInverse($this, $key);
        }
        $this->touch();
        $collection = $this->hasManyRelationships->get($key);
        $collection->push($model);
        $this->doDirtyCheck();
        return $this;
    }

    /**
     * Clears a has-many relationship collection, sets an attribute to null, or sets a has-one relationship to null.
     *
     * @api
     * @param   string  $key    The property key.
     * @return  self
     */
    public function clear($key)
    {
        if (true === $this->isAttribute($key)) {
            return $this->setAttribute($key, null);
        }
        if (true === $this->isHasOne($key)) {
            return $this->setHasOne($key, null);
        }
        if (true === $this->isInverse($key)) {
            throw ModelException::cannotModifyInverse($this, $key);
        }
        if (true === $this->isHasMany($key)) {
            $collection = $this->hasManyRelationships->get($key);
            $collection->clear();
            $this->doDirtyCheck();
            return $this;
        }
        return $this;
    }

    /**
     * Removes a specific Model from a has-many relationship collection.
     *
     * @api
     * @param   string  $key    The has-many relationship key.
     * @param   Model   $model  The model to remove from the collection.
     * @return  self
     */
    public function remove($key, Model $model)
    {
        if (false === $this->isHasMany($key)) {
            return $this;
        }
        if (true === $this->isInverse($key)) {
            throw ModelException::cannotModifyInverse($this, $key);
        }
        $this->touch();
        $collection = $this->hasManyRelationships->get($key);
        $collection->remove($model);
        $this->doDirtyCheck();
        return $this;
    }

    /**
     * Sets a model property: an attribute value, a has-one model, or an entire has-many model collection.
     * Note: To push/remove a single Model into a has-many collection, or clear a collection, use @see push(), remove() and clear().
     * Is a proxy for @see setAttribute() and setRelationship()
     *
     * @api
     * @param   string  $key                The property field key.
     * @param   Model|Collection|null|mixed The value to set.
     * @return  self.
     */
    public function set($key, $value)
    {
        if (true === $this->isAttribute($key)) {
            return $this->setAttribute($key, $value);
        }
        return $this->setRelationship($key, $value);
    }

    /**
     * Sets an attribute value.
     * Will convert the value to the proper, internal PHP/Modlr data type.
     * Will do a dirty check immediately after setting.
     *
     * @param   string  $key    The attribute key (field) name.
     * @param   mixed   $value  The value to apply.
     * @return  self
     */
    protected function setAttribute($key, $value)
    {
        if (true === $this->isCalculatedAttribute($key)) {
            return $this;
        }
        $this->touch();
        $value = $this->convertAttributeValue($key, $value);
        $this->attributes->set($key, $value);
        $this->doDirtyCheck();
        return $this;
    }

    protected function convertAttributeValue($key, $value)
    {
        return $this->store->convertAttributeValue($this->getDataType($key), $value);
    }

    /**
     * Gets a data type from an attribute key.
     *
     * @param   string  $key The attribute key.
     * @return  string
     */
    protected function getDataType($key)
    {
        return $this->getMetadata()->getAttribute($key)->dataType;
    }

    /**
     * Sets a relationship value.
     *
     * @param   string      $key
     * @param   Model|null  $value
     * @return  self
     */
    protected function setRelationship($key, $value)
    {
        if (true === $this->isHasOne($key)) {
            return $this->setHasOne($key, $value);
        }
        if (true === $this->isHasMany($key)) {
            throw new \RuntimeException('You cannot set a hasMany relationship directly. Please access using push(), clear(), and/or remove()');
        }
        return $this;
    }

    /**
     * Sets a has-one relationship.
     *
     * @param   string      $key    The relationship key (field) name.
     * @param   Model|null  $model  The model to relate.
     * @return  self
     */
    protected function setHasOne($key, Model $model = null)
    {
        if (true === $this->isInverse($key)) {
            throw ModelException::cannotModifyInverse($this, $key);
        }
        if (null !== $model) {
            $this->validateRelSet($key, $model->getType());
        }
        $this->touch();
        $this->hasOneRelationships->set($key, $model);
        $this->doDirtyCheck();
        return $this;
    }

    /**
     * Validates that the model type (from a Model or Collection instance) can be set to the relationship field.
     *
     * @param   string  $relKey The relationship field key.
     * @param   string  $type   The model type that is being related.
     * @return  self
     */
    protected function validateRelSet($relKey, $type)
    {
        $relMeta = $this->getMetadata()->getRelationship($relKey);
        $relatedModelMeta = $this->store->getMetadataForRelationship($relMeta);
        $this->store->validateRelationshipSet($relatedModelMeta, $type);
        return $this;
    }

    /**
     * Determines if the model uses a particlar mixin.
     *
     * @api
     * @param   string  $name
     * @return  bool
     */
    public function usesMixin($name)
    {
        return $this->metadata->hasMixin($name);
    }

    /**
     * Saves the model.
     *
     * @api
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
     * @api
     * @return  self
     */
    public function rollback()
    {
        $this->attributes->rollback();
        $this->hasOneRelationships->rollback();
        $this->hasManyRelationships->rollback();
        $this->doDirtyCheck();
        return $this;
    }

    /**
     * Reloads the model from the database.
     *
     * @api
     * @return  self
     */
    public function reload()
    {
        return $this->touch(true);
    }

    /**
     * Restores an in-memory deleted object back to the database.
     *
     * @api
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
     * @api
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
            $this->state->setLoaded();
            // @todo Should this trigger a postReload event? Likely not.
        }
        return $this;
    }

    /**
     * Applies an array of raw model properties (attributes and relationships) to the model instance.
     *
     * @todo    Confirm that we want this method. It's currently used for creating and updating via the API adapter. Also see initialize()
     * @param   array   $properties     The properties to apply.
     * @return  self
     */
    public function apply(array $properties)
    {
        $properties = $this->applyDefaultAttrValues($properties);
        foreach ($properties as $key => $value) {
            if (true === $this->isAttribute($key)) {
                $this->set($key, $value);
                continue;
            }
            if (true === $this->isHasOne($key)) {
                if (empty($value)) {
                    $this->clear($key);
                    continue;
                }
                $value = $this->store->loadProxyModel($value['type'], $value['id']);
                $this->set($key, $value);
                continue;
            }

        }

        foreach ($this->getMetadata()->getRelationships() as $key => $relMeta) {
            if (true === $relMeta->isOne()) {
                continue;
            }
            // Array key exists must exist to determine if the
            if (!isset($properties[$key]) || true === $relMeta->isInverse) {
                continue;
            }

            $this->clear($key);
            $collection = $this->store->createCollection($relMeta, $properties[$key]);
            foreach ($collection->allWithoutLoad() as $value) {
                $this->push($key, $value);
            }
        }
        $this->doDirtyCheck();
        return $this;
    }

    /**
     * Initializes the model and loads its attributes and relationships.
     *
     * @todo    Made public so collections can initialize models. Not sure if we want this??
     * @param   Record|null   $record     The db attributes and relationships to apply.
     * @return  self
     */
    public function initialize(Record $record = null)
    {
        $hasOne = [];
        $hasMany = [];
        $attributes = [];

        if (null !== $record) {
            $attributes = $this->applyDefaultAttrValues($attributes);
            foreach ($record->getProperties() as $key => $value) {
                if (true === $this->isAttribute($key)) {
                    // Load attribute.
                    $attributes[$key] = $this->convertAttributeValue($key, $value);
                    continue;
                }
                if (true === $this->isHasOne($key)) {
                    // Load hasOne relationship.
                    $hasOne[$key] = $this->store->loadProxyModel($value['type'], $value['id']);
                    continue;
                }
            }
        }

        foreach ($this->getMetadata()->getRelationships() as $key => $relMeta) {
            if (true === $relMeta->isOne()) {
                continue;
            }
            if (true === $relMeta->isInverse) {
                $hasMany[$key] = $this->store->createInverseCollection($relMeta, $this);
            } else {
                $references = (null === $record || !isset($record->getProperties()[$key])) ? [] : $record->getProperties()[$key];
                $hasMany[$key] = $this->store->createCollection($relMeta, $references);
            }
        }

        $this->attributes           = (null === $this->attributes) ? new Attributes($attributes) : $this->attributes->replace($attributes);
        $this->hasOneRelationships  = (null === $this->hasOneRelationships) ? new Relationships\HasOne($hasOne) : $this->hasOneRelationships->replace($hasOne);
        $this->hasManyRelationships = (null === $this->hasManyRelationships) ? new Relationships\HasMany($hasMany) : $this->hasManyRelationships->replace($hasMany);
        $this->doDirtyCheck();
        return $this;
    }

    /**
     * Applies default attribute values from metadata, if set.
     *
     * @param   array   $attributes     The attributes to apply the defaults to.
     * @return  array
     */
    protected function applyDefaultAttrValues(array $attributes = [])
    {
        // Set defaults for each attribute.
        foreach ($this->getMetadata()->getAttributes() as $key => $attrMeta) {
            if (!isset($attrMeta->defaultValue)) {
                continue;
            }
            $attributes[$key] = $this->convertAttributeValue($key, $attrMeta->defaultValue);
        }

        // Set defaults for the entire entity.
        foreach ($this->getMetadata()->defaultValues as $key => $value) {
            $attributes[$key] = $this->convertAttributeValue($key, $value);
        }
        return $attributes;
    }

    /**
     * Determines if the model is currently dirty.
     * Checks against the attribute and relationship dirty states.
     *
     * @api
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
     * Does a dirty check and sets the state to this model.
     *
     * @return  self
     */
    protected function doDirtyCheck()
    {
        $this->state->setDirty($this->isDirty());
        return $this;
    }

    /**
     * Gets the current change set of attributes and relationships.
     *
     * @api
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
}
