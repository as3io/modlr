<?php

namespace As3\Modlr\Models\Collections;

use As3\Modlr\Metadata\EntityMetadata;
use As3\Modlr\Models\AbstractModel;
use As3\Modlr\Models\Model;
use As3\Modlr\Store\Store;

/**
 * Model collection that contains record representations from a persistence (database) layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class InverseCollection extends ModelCollection
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
     * {@inheritdoc}
     *
     * Overwritten to prevent modification.
     *
     * @throws  \BadMethodCallException
     */
    public function clear()
    {
       throw new \BadMethodCallException('You cannot clear inverse collections.');
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
    public function getQueryField()
    {
        return $this->inverseField;
    }

    /**
     * {@inheritDoc}
     *
     * Overwritten to always return false.
     *
     */
    public function isDirty()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * Overwritten to prevent modification.
     *
     * @throws  \BadMethodCallException
     */
    public function push(AbstractModel $model)
    {
        throw new \BadMethodCallException('You cannot push to inverse collections.');
    }

    /**
     * {@inheritdoc}
     *
     * Overwritten to prevent modification.
     *
     * @throws  \BadMethodCallException
     */
    public function remove(AbstractModel $model)
    {
        throw new \BadMethodCallException('You cannot remove from an inverse collections.');
    }
}
