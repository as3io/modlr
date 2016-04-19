<?php

namespace As3\Modlr\Models;

use As3\Modlr\Models\Relationships;
use As3\Modlr\Persister\Record;
use As3\Modlr\Store\Store;
use As3\Modlr\Metadata\EntityMetadata;

/**
 * Represents a data record from a persistence (database) layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Model extends AbstractModel
{
    /**
     * The id value of this model.
     * Always converted to a string when in the model context.
     *
     * @var string
     */
    protected $identifier;

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
     * Constructor.
     *
     * @param   EntityMetadata  $metadata       The internal entity metadata that supports this Model.
     * @param   string          $identifier     The database identifier.
     * @param   Store           $store          The model store service for handling persistence operations.
     * @param   array|null      $properties     The model's properties from the db layer to init the model with. New models will constructed with a null record.
     */
    public function __construct(EntityMetadata $metadata, $identifier, Store $store, array $properties = null)
    {
        $this->identifier = $identifier;
        parent::__construct($metadata, $store, $properties);
    }

    /**
     * Cloner.
     * Ensures sub objects are also cloned.
     *
     */
    public function __clone()
    {
        parent::__clone();
        $this->hasOneRelationships = clone $this->hasOneRelationships;
        $this->hasManyRelationships = clone $this->hasManyRelationships;
    }

    /**
     * {@inheritdoc}
     *
     * Overloaded to support relationships.
     *
     */
    public function apply(array $properties)
    {
        foreach ($properties as $key => $value) {
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
            if (!isset($properties[$key]) || true === $relMeta->isInverse) {
                continue;
            }
            $this->clear($key);
            $collection = $this->store->createCollection($relMeta, $properties[$key]);
            foreach ($collection->allWithoutLoad() as $value) {
                $this->push($key, $value);
            }
        }
        return parent::apply($properties);
    }

    /**
     * {@inheritdoc}
     *
     * Overloaded to support relationships.
     */
    public function clear($key)
    {
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
        return parent::clear($key);
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
     * {@inheritdoc}
     *
     * Overloaded to support relationships.
     *
     */
    public function get($key)
    {
        if (true === $this->isRelationship($key)) {
            return $this->getRelationship($key);
        }
        return parent::get($key);
    }

    /**
     * {@inheritdoc}
     *
     * Overloaded to support relationships.
     *
     */
    public function getChangeSet()
    {
        $changeset = parent::getChangeSet();
        $changeset['hasOne']  = $this->filterNotSavedProperties($this->hasOneRelationships->calculateChangeSet());
        $changeset['hasMany'] = $this->filterNotSavedProperties($this->hasManyRelationships->calculateChangeSet());
        return $changeset;
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
     * {@inheritdoc}
     *
     * Overloaded to support relationships.
     *
     */
    public function initialize(array $properties = null)
    {
        $hasOne = [];
        $hasMany = [];

        if (null !== $properties) {
            foreach ($properties as $key => $value) {
                if (true === $this->isHasOne($key)) {
                    // Load hasOne relationship.
                    $hasOne[$key] = $this->getStore()->loadProxyModel($value['type'], $value['id']);
                    continue;
                }
            }
        }

        foreach ($this->getMetadata()->getRelationships() as $key => $relMeta) {
            if (true === $relMeta->isOne()) {
                continue;
            }
            if (true === $relMeta->isInverse) {
                $hasMany[$key] = $this->getStore()->createInverseCollection($relMeta, $this);
            } else {
                $references = !isset($properties[$key]) ? [] : $properties[$key];
                $hasMany[$key] = $this->getStore()->createCollection($relMeta, $references);
            }
        }

        $this->hasOneRelationships  = (null === $this->hasOneRelationships) ? new Relationships\HasOne($hasOne) : $this->hasOneRelationships->replace($hasOne);
        $this->hasManyRelationships = (null === $this->hasManyRelationships) ? new Relationships\HasMany($hasMany) : $this->hasManyRelationships->replace($hasMany);
        return parent::initialize($properties);
    }

    /**
     * {@inheritdoc}
     *
     * Overloaded to support relationships.
     *
     */
    public function isDirty()
    {
        return true === parent::isDirty()
            || true === $this->hasOneRelationships->areDirty()
            || true === $this->hasManyRelationships->areDirty()
        ;
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
     * {@inheritdoc}
     * Overloaded to support relationship rollback.
     */
    public function rollback()
    {
        $this->hasOneRelationships->rollback();
        $this->hasManyRelationships->rollback();
        return parent::rollback();
    }

    /**
     * {@inheritdoc}
     *
     * Overloaded to support relationships.
     * Sets a model property: an attribute value, a has-one model, or an entire has-many model collection.
     * Note: To push/remove a single Model into a has-many collection, or clear a collection, use @see push(), remove() and clear().
     *
     */
    public function set($key, $value)
    {
        if (true === $this->isRelationship($key)) {
            return $this->setRelationship($key, $value);
        }
        return parent::set($key, $value);
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
     * {@inheritdoc}
     *
     * Overloaded to support relationships.
     */
    protected function filterNotSavedProperties(array $properties)
    {
        foreach ($this->getMetadata()->getRelationships() as $fieldKey => $propMeta) {
            if (true === $propMeta->shouldSave() || !isset($properties[$fieldKey])) {
                continue;
            }
            unset($properties[$fieldKey]);
        }
        return parent::filterNotSavedProperties($properties);
    }

    /**
     * {@inheritdoc}
     *
     * Overloaded to support global model defaults.
     *
     */
    protected function applyDefaultAttrValues(array $attributes = [])
    {
        $attributes = parent::applyDefaultAttrValues($attributes);

        // Set defaults for the entire entity.
        foreach ($this->getMetadata()->defaultValues as $key => $value) {
            if (isset($attributes[$key])) {
                continue;
            }
            $attributes[$key] = $this->convertAttributeValue($key, $value);
        }
        return $attributes;
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
     * {@inheritdoc}
     *
     * Overloaded to handle loading from the database.
     * If the model is currently empty, it will query the database and fill/load the model.
     *
     */
    protected function touch($force = false)
    {
        if (true === $this->getState()->is('deleted')) {
            return $this;
        }
        if (true === $this->getState()->is('empty') || true === $force) {
            $record = $this->store->retrieveRecord($this->getType(), $this->getId());
            $this->initialize($record->getProperties());
            $this->getState()->setLoaded();
        }
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
        $relatedModelMeta = $this->getStore()->getMetadataForRelationship($relMeta);
        $this->getStore()->validateRelationshipSet($relatedModelMeta, $type);
        return $this;
    }
}
