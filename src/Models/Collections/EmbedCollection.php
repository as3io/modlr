<?php

namespace As3\Modlr\Models\Collections;

use As3\Modlr\Metadata\EmbedMetadata;
use As3\Modlr\Models\AbstractModel;
use As3\Modlr\Models\Embed;
use As3\Modlr\Store\Store;

/**
 * Model collection that contains embedded fragments from a persistence (database) layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class EmbedCollection extends AbstractCollection
{
    /**
     * @var EmbedMetadata
     */
    protected $metadata;

    /**
     * Constructor.
     *
     * @param   EmbedMetadata  $metadata
     * @param   Store           $store
     * @param   AbstractModel[] $models
     */
    public function __construct(EmbedMetadata $metadata, Store $store, array $models = [])
    {
        $this->metadata = $metadata;
        parent::__construct($store, $models, count($models));
    }

    /**
     * Creates a new Embed model instance based on the collection
     *
     * @return  Embed
     */
    public function createNewEmbed()
    {
        $embed = $this->store->loadEmbed($this->getMetadata(), []);
        $embed->getState()->setNew();
        return $embed;
    }

    /**
     * Gets the unique hash for this collection.
     *
     * @return  string
     */
    public function getHash()
    {
        $hash = [];
        foreach ($this as $embed) {
            $hash[] = $embed->getHash();
        }
        sort($hash);
        return md5(serialize($hash));
    }

    /**
     * Gets the metadata for the model collection.
     *
     * @return  EmbedMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->getMetadata()->name;
    }

    /**
     * {@inheritdoc}
     */
    public function isDirty()
    {
        if (true === $this->hasDirtyModels()) {
            return true;
        }
        return parent::isDirty();
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAdd(AbstractModel $model)
    {
        $this->validateModelClass($model);
        $this->store->validateEmbedSet($this->getMetadata(), $model->getName());
    }

    /**
     * Validates that the model class instance is supported.
     *
     * @param   AbstractModel   $model
     * @throws  \InvalidArgumentException
     */
    protected function validateModelClass(AbstractModel $model)
    {
        if (!$model instanceof Embed) {
            throw new \InvalidArgumentExcepton('The model must be an instanceof of Embed');
        }
    }
}
