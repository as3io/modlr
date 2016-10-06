<?php

namespace As3\Modlr\Models;

use As3\Modlr\Metadata\Interfaces\AttributeInterface;
use As3\Modlr\Store\Store;

/**
 * Represents a record from a persistence (database) layer.
 * Can either be a root record, or an embedded fragment of a root record.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class AbstractModel
{
    /**
     * The model's attributes
     *
     * @var Attributes
     */
    protected $attributes;

    /**
     * The Model's has-one embeds
     *
     * @var Embeds\HasOne
     */
    protected $hasOneEmbeds;

    /**
     * The Model's has-many embeds
     *
     * @var Embeds\HasMany
     */
    protected $hasManyEmbeds;

    /**
     * The metadata that defines this Model.
     *
     * @var AttributeInterface
     */
    protected $metadata;

    /**
     * The model state.
     *
     * @var State
     */
    protected $state;

    /**
     * The Model Store for handling lifecycle operations.
     *
     * @var Store
     */
    protected $store;

    /**
     * Constructor.
     *
     * @param   AttributeInterface  $metadata
     * @param   Store               $store
     * @param   array|null          $properties
     */
    public function __construct(AttributeInterface $metadata, Store $store, array $properties = null)
    {
        $this->state = new State();
        $this->metadata = $metadata;
        $this->store = $store;
        $this->initialize($properties);
    }

    /**
     * Cloner.
     * Ensures sub objects are also cloned.
     *
     */
    public function __clone()
    {
        $this->attributes = clone $this->attributes;
        $this->hasOneEmbeds = clone $this->hasOneEmbeds;
        $this->hasManyEmbeds = clone $this->hasManyEmbeds;
        $this->state = clone $this->state;
    }

    /**
     * Applies an array of raw model properties to the model instance.
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

            if (true === $this->isEmbedHasOne($key)) {
                if (empty($value)) {
                    $this->clear($key);
                    continue;
                }
                $embed = $this->get($key) ?: $this->createEmbedFor($key, $value);
                if (!is_array($value)) {
                    continue;
                }
                $embed->apply($value);
                $this->set($key, $embed);
                continue;
            }
        }

        foreach ($this->getMetadata()->getEmbeds() as $key => $embeddedPropMeta) {
            if (true === $embeddedPropMeta->isOne() || !isset($properties[$key])) {
                continue;
            }

            $collection = $this->getStore()->createEmbedCollection($embeddedPropMeta, $properties[$key]);
            if ($collection->getHash() === $this->get($key)->getHash()) {
                // The current collection is the same as the incoming collection.
                continue;
            }

            // The incoming collection is different. Clear the current collection and push the new values.
            $this->clear($key);
            foreach ($collection as $value) {
                $this->pushEmbed($key, $value);
            }
        }

        $this->doDirtyCheck();
        return $this;
    }

    /**
     * Clears a property value.
     * For an attribute, will set the value to null.
     * For collections, will clear the collection contents.
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
        if (true === $this->isEmbedHasOne($key)) {
            return $this->setEmbedHasOne($key, null);
        }
        if (true === $this->isEmbedHasMany($key)) {
            $collection = $this->hasManyEmbeds->get($key);
            $collection->clear();
            $this->doDirtyCheck();
            return $this;
        }
        return $this;
    }

    /**
     * Creates a new Embed model instance for the provided property key.
     *
     * @param   string  $key
     * @return  Embed
     * @throws  \RuntimeException
     */
    public function createEmbedFor($key)
    {
        if (false === $this->isEmbed($key)) {
            throw new \RuntimeException(sprintf('Unable to create an Embed instance for property key "%s" - the property is not an embed.', $key));
        }

        $embedMeta = $this->getMetadata()->getEmbed($key)->embedMeta;
        $embed = $this->getStore()->loadEmbed($embedMeta, []);
        $embed->getState()->setNew();
        return $embed;
    }

    /**
     * Gets a model property.
     * Returns null if the property does not exist on the model or is not set.
     *
     * @api
     * @param   string  $key    The property field key.
     * @return  Model|Model[]|Embed|Collections\EmbedCollection|null|mixed
     */
    public function get($key)
    {
        if (true === $this->isAttribute($key)) {
            return $this->getAttribute($key);
        }
        if (true === $this->isEmbed($key)) {
            return $this->getEmbed($key);
        }
    }

    /**
     * Gets the current change set of properties.
     *
     * @api
     * @return  array
     */
    public function getChangeSet()
    {
        return [
            'attributes'    => $this->filterNotSavedProperties($this->attributes->calculateChangeSet()),
            'embedOne'      => $this->hasOneEmbeds->calculateChangeSet(),
            'embedMany'     => $this->hasManyEmbeds->calculateChangeSet(),
        ];
    }

    /**
     * Gets the composite key of the model.
     *
     * @api
     * @return  string
     */
    abstract public function getCompositeKey();

    /**
     * Gets the metadata for this model.
     *
     * @api
     * @return  AttributeInterface
     */
    public function getMetadata()
    {
        return $this->metadata;
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
     * Gets the model store.
     *
     * @api
     * @return  Store
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Initializes the model and loads its attributes and relationships.
     *
     * @todo    Made public so collections can initialize models. Not sure if we want this??
     * @param   array|null      $properties     The db properties to apply.
     * @return  self
     */
    public function initialize(array $properties = null)
    {
        $attributes = [];
        $embedOne = [];
        $embedMany = [];

        if (null !== $properties) {
            $attributes = $this->applyDefaultAttrValues($attributes);
            foreach ($properties as $key => $value) {
                if (true === $this->isAttribute($key)) {
                    // Load attribute.
                    $attributes[$key] = $this->convertAttributeValue($key, $value);
                } else if (true === $this->isEmbedHasOne($key) && is_array($value)) {
                    // Load embed one.
                    $embedOne[$key] = $this->getStore()->loadEmbed($this->getMetadata()->getEmbed($key)->embedMeta, $value);
                }
            }
        }

        foreach ($this->getMetadata()->getEmbeds() as $key => $embeddedPropMeta) {
            // Always load embedded collections, regardless if data is set.
            if (true === $embeddedPropMeta->isOne()) {
                continue;
            }
            $embeds = !isset($properties[$key]) ? [] : $properties[$key];
            $embedMany[$key] = $this->getStore()->createEmbedCollection($embeddedPropMeta, $embeds);
        }

        $this->attributes    = (null === $this->attributes) ? new Attributes($attributes) : $this->attributes->replace($attributes);
        $this->hasOneEmbeds  = (null === $this->hasOneEmbeds) ? new Embeds\HasOne($embedOne) : $this->hasOneEmbeds->replace($embedOne);
        $this->hasManyEmbeds = (null === $this->hasManyEmbeds) ? new Embeds\HasMany($embedMany) : $this->hasManyEmbeds->replace($embedMany);

        if (true === $this->getState()->is('new')) {
            // Ensure default values are applied to new models.
            $this->apply([]);
        }

        $this->doDirtyCheck();
        return $this;
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
     * Determines if the model is currently dirty.
     *
     * @api
     * @return  bool
     */
    public function isDirty()
    {
        return true === $this->attributes->areDirty()
            || true === $this->hasOneEmbeds->areDirty()
            || true === $this->hasManyEmbeds->areDirty()
        ;
    }

    /**
     * Determines if a property key is an embedded property.
     *
     * @api
     * @param   string  $key    The property key.
     * @return  bool
     */
    public function isEmbed($key)
    {
        return $this->getMetadata()->hasEmbed($key);
    }

    /**
     * Determines if a property key is a has-many embed.
     *
     * @api
     * @param   string  $key    The property key.
     * @return  bool
     */
    public function isEmbedHasMany($key)
    {
        if (false === $this->isEmbed($key)) {
            return false;
        }
        return $this->getMetadata()->getEmbed($key)->isMany();
    }

    /**
     * Determines if a property key is a has-one embed.
     *
     * @api
     * @param   string  $key    The property key.
     * @return  bool
     */
    public function isEmbedHasOne($key)
    {
        if (false === $this->isEmbed($key)) {
            return false;
        }
        return $this->getMetadata()->getEmbed($key)->isOne();
    }

    /**
     * Pushes an Embed into a has-many embed collection.
     * This method must be used for has-many embeds. Direct set is not supported.
     * To completely replace call clear() first and then pushEmbed() the new Embeds.
     *
     * @api
     * @param   string  $key
     * @param   Embed   $embed
     * @return  self
     */
    public function pushEmbed($key, Embed $embed)
    {
        if (true === $this->isEmbedHasOne($key)) {
            return $this->setEmbedHasOne($key, $embed);
        }
        if (false === $this->isEmbedHasMany($key)) {
            return $this;
        }
        $this->touch();
        $collection = $this->hasManyEmbeds->get($key);
        $collection->push($embed);
        $this->doDirtyCheck();
        return $this;
    }

    /**
     * Removes a specific Embed from a has-many embed collection.
     *
     * @api
     * @param   string  $key    The has-many embed key.
     * @param   Embed   $embed  The embed to remove from the collection.
     * @return  self
     */
    public function removeEmbed($key, Embed $embed)
    {
        if (false === $this->isEmbedHasMany($key)) {
            return $this;
        }
        $this->touch();
        $collection = $this->hasManyEmbeds->get($key);
        $collection->remove($embed);
        $this->doDirtyCheck();
        return $this;
    }

    /**
     * Rolls back a model to its original values.
     *
     * @api
     * @return  self
     */
    public function rollback()
    {
        $this->attributes->rollback();
        $this->hasOneEmbeds->rollback();
        $this->hasManyEmbeds->rollback();
        $this->doDirtyCheck();
        return $this;
    }

    /**
     * Sets a model property.
     *
     * @api
     * @param   string  $key            The property field key.
     * @param   Model|Embed|null|mixed  The value to set.
     * @return  self.
     */
    public function set($key, $value)
    {
        if (true === $this->isAttribute($key)) {
            return $this->setAttribute($key, $value);
        }
        if (true === $this->isEmbed($key)) {
            return $this->setEmbed($key, $value);
        }
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
        return $this->getMetadata()->hasMixin($name);
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
            if (!isset($attrMeta->defaultValue) || isset($attributes[$key])) {
                continue;
            }
            $attributes[$key] = $this->convertAttributeValue($key, $attrMeta->defaultValue);
        }
        return $attributes;
    }

    /**
     * Converts an attribute value to the appropriate data type.
     *
     * @param   string  $key
     * @param   mixed   $value
     * @return  mixed
     */
    protected function convertAttributeValue($key, $value)
    {
        return $this->store->convertAttributeValue($this->getDataType($key), $value);
    }

    /**
     * Does a dirty check and sets the state to this model.
     *
     * @return  self
     */
    protected function doDirtyCheck()
    {
        $this->getState()->setDirty($this->isDirty());
        return $this;
    }

    /**
     * Removes properties marked as non-saved.
     *
     * @param   array   $properties
     * @return  array
     */
    protected function filterNotSavedProperties(array $properties)
    {
        foreach ($this->getMetadata()->getAttributes() as $fieldKey => $propMeta) {
            if (true === $propMeta->shouldSave() || !isset($properties[$fieldKey])) {
                continue;
            }
            unset($properties[$fieldKey]);
        }
        return $properties;
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
     * Gets an embed value.
     *
     * @param   string  $key    The embed key (field) name.
     * @return  Embed|Collections\EmbedCollection|null
     */
    protected function getEmbed($key)
    {
        if (true === $this->isEmbedHasOne($key)) {
            $this->touch();
            return $this->hasOneEmbeds->get($key);
        }
        if (true === $this->isEmbedHasMany($key)) {
            $this->touch();
            return $this->hasManyEmbeds->get($key);
        }
        return null;
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

    /**
     * Sets an embed value.
     *
     * @param   string      $key
     * @param   Embed|null  $value
     * @return  self
     */
    protected function setEmbed($key, $value)
    {
        if (true === $this->isEmbedHasOne($key)) {
            return $this->setEmbedHasOne($key, $value);
        }
        if (true === $this->isEmbedHasMany($key)) {
            throw new \RuntimeException('You cannot set a hasMany embed directly. Please access using pushEmbed(), clear(), and/or remove()');
        }
        return $this;
    }

    /**
     * Sets a has-one embed.
     *
     * @param   string      $key    The embed key (field) name.
     * @param   Embed|null  $embed  The embed to relate.
     * @return  self
     */
    protected function setEmbedHasOne($key, Embed $embed = null)
    {
        if (null !== $embed) {
            $this->validateEmbedSet($key, $embed->getName());
        }
        $this->touch();
        $this->hasOneEmbeds->set($key, $embed);
        $this->doDirtyCheck();
        return $this;
    }

    /**
     * Touches the model.
     * Must be handled the the extending class.
     *
     * @param   bool    $force  Whether to force the load, even if the model is currently loaded.
     * @return  self
     */
    protected function touch($force = false)
    {
        return $this;
    }

    /**
     * Validates that the model type (from a Model or Collection instance) can be set to the relationship field.
     *
     * @param   string  $embedKey   The embed field key.
     * @param   string  $embedName  The embed name that is being set.
     * @return  self
     */
    protected function validateEmbedSet($embedKey, $embedName)
    {
        $embededPropMeta = $this->getMetadata()->getEmbed($embedKey);
        $this->getStore()->validateEmbedSet($embededPropMeta->embedMeta, $embedName);
        return $this;
    }
}
