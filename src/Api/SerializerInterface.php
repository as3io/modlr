<?php

namespace As3\Modlr\Api;

use As3\Modlr\Metadata\EntityMetadata;
use As3\Modlr\Models\Collection;
use As3\Modlr\Models\Model;

/**
 * Interface for serializing models in the implementing format.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface SerializerInterface
{
    /**
     * Serializes a Model object into the appropriate format.
     *
     * @param   Model|null          $model
     * @param   AdapterInterface    $adapter
     * @return  string|array    Depending on depth
     */
    public function serialize(Model $model = null, AdapterInterface $adapter);

    /**
     * Serializes a Collection object into the appropriate format.
     *
     * @param   Collection          $collection
     * @param   AdapterInterface    $adapter
     * @return  string|array    Depending on depth
     */
    public function serializeCollection(Collection $collection, AdapterInterface $adapter);

    /**
     * Serializes an array of Model objects into the appropriate format.
     *
     * @param   Model[]             $models
     * @param   AdapterInterface    $adapter
     * @return  string|array    Depending on depth
     */
    public function serializeArray(array $models, AdapterInterface $adapter);

    /**
     * Gets a serialized error response.
     *
     * @return  string
     */
    public function serializeError($title, $detail, $httpCode);
}
