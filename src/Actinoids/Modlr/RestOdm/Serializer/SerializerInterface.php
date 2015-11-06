<?php

namespace Actinoids\Modlr\RestOdm\Serializer;

use Actinoids\Modlr\RestOdm\Models\Model;
use Actinoids\Modlr\RestOdm\Models\Collection;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Adapter\AdapterInterface;

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
