<?php

namespace As3\Modlr\Models;

use As3\Modlr\Metadata\EmbedMetadata;
use As3\Modlr\Store\Store;

/**
 * Represents an embedded record fragment of a root level model.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Embed extends AbstractModel
{
    /**
     * The metadata that defines this Model.
     *
     * @var EmbedMetadata
     */
    protected $metadata;

    /**
     * Constructor.
     *
     * @param   EmbedMetadata   $metadata   The internal embed metadata that supports this Embed.
     * @param   Store           $store      The model store service for handling persistence operations.
     * @param   array|null      $properties The embed's properties from the db layer to init the embed with. New embeds will constructed with a null record.
     */
    public function __construct(EmbedMetadata $metadata, Store $store, array $properties = null)
    {
        parent::__construct($metadata, $store, $properties);
        $this->getState()->setLoaded();
    }

    /**
     * {@inheritdoc}
     */
    public function getCompositeKey()
    {
        return spl_object_hash($this);
    }

    /**
     * Gets the unique hash for this embed.
     *
     * @return  string
     */
    public function getHash()
    {
        $hash = [];
        foreach ($this->metadata->getAttributes() as $key => $attrMeta) {
            $value = $this->get($key);
            if (null === $value) {
                $hash[$key] = $value;
                continue;
            }
            switch ($attrMeta->dataType) {
                case 'date':
                    $value = $value->getTimestamp();
                    break;
                case 'object':
                    $value = (array) $object;
                    ksort($value);
                    break;
                case 'mixed':
                    $value = serialize($value);
                    break;
                case 'array':
                    sort($value);
                    break;
            }
            $hash[$key] = $value;
        }
        foreach ($this->metadata->getEmbeds() as $key => $embbedPropMeta) {
            if (true === $embbedPropMeta->isOne()) {
                $embed = $this->get($key);
                $hash[$key] = (null === $embed) ? null : $embed->getHash();
            } else {
                $collection = $this->get($key);
                $hash[$key] = $collection->getHash();
            }
        }
        ksort($hash);
        return md5(serialize($hash));
    }

    /**
     * Gets the metadata for this model.
     *
     * @api
     * @return  EmbedMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }


    /**
     * Gets the embed name.
     *
     * @api
     * @return  string
     */
    public function getName()
    {
        return $this->getMetadata()->name;
    }
}
