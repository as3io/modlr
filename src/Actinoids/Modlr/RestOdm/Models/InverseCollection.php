<?php

namespace As3\Modlr\RestOdm\Models;

use As3\Modlr\RestOdm\Store\Store;
use As3\Modlr\RestOdm\Metadata\EntityMetadata;

/**
 * Model collection that contains record representations from a persistence (database) layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class InverseCollection extends AbstractCollection
{
    /**
     * The owning model of the inverse relationship.
     *
     * @var Model
     */
    private $owner;

    /**
     * The inverse relationship query field.
     *
     * @var string
     */
    private $inverseField;

    /**
     * {@inheritDoc}
     */
    protected $loaded = false;

    /**
     * {@inheritDoc}
     * @param   Model   $owner
     * @param   string  $inverseField
     */
    public function __construct(EntityMetadata $metadata, Store $store, Model $owner, $inverseField)
    {
        parent::__construct($metadata, $store, []);
        $this->owner = $owner;
        $this->inverseField = $inverseField;
    }

    /**
     * Gets the model that owns this inverse collection.
     *
     * @return  Model
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifiers($onlyUnloaded = true)
    {
        if (true === $this->loaded) {
            return [];
        }
        return [$this->owner->getId()];
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryField()
    {
        return $this->inverseField;
    }

    /**
     * {@inheritDoc}
     */
    public function isDirty()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     *
     * Overwritten to ensure the collection is loaded, since references aren't sent to inverse collections.
     */
    public function allWithoutLoad()
    {
        $this->loadFromStore();
        return parent::allWithoutLoad();
    }
}
