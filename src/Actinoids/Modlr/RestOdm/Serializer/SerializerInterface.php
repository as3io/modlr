<?php

namespace Actinoids\Modlr\RestOdm\Serializer;

use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Adapter\AdapterInterface;
use Actinoids\Modlr\RestOdm\Struct;

/**
 * Interface for serializing resources in the implementing format.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface SerializerInterface
{
    /**
     * Gets a serialized error response.
     *
     * @return  string
     */
    public function serializeError($title, $detail, $httpCode);

    /**
     * Serializes a Struct\Resource object into a Rest\RestPayload object
     *
     * @param   Struct\Resource     $resource
     * @param   AdapterInterface    $adapter
     * @return  mixed
     */
    public function serialize(Struct\Resource $resource, AdapterInterface $adapter);

    /**
     * Serializes a Struct\Identifier object into the appropriate format.
     *
     * @param   Struct\Identifier   $identifer
     * @param   AdapterInterface    $adapter
     * @return  array
     */
    public function serializeIdentifier(Struct\Identifier $identifier, AdapterInterface $adapter);

    /**
     * Serializes a Struct\Entity object into the appropriate format.
     *
     * @param   Struct\Entity       $entity
     * @param   AdapterInterface    $adapter
     * @return  array
     */
    public function serializeEntity(Struct\Entity $entity, AdapterInterface $adapter);

    /**
     * Serializes a Struct\Collection object into the appropriate format.
     *
     * @param   Struct\Collection   $collection
     * @param   AdapterInterface    $adapter
     * @return  array
     */
    public function serializeCollection(Struct\Collection $collection, AdapterInterface $adapter);
}
