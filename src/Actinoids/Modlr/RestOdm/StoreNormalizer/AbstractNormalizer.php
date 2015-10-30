<?php

namespace Actinoids\Modlr\RestOdm\StoreNormalizer;

use Actinoids\Modlr\RestOdm\Struct;
use Actinoids\Modlr\RestOdm\Rest\RestPayload;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\StoreAdapter\JsonApiAdapter;
use Actinoids\Modlr\RestOdm\Hydrator\HydratorInterface;

/**
 * Abstract implementation of normalizing REST payloads into Struct\Resources.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class AbstractNormalizer implements NormalizerInterface
{
    /**
     * {@inheritDoc}
     */
    public function normalize(RestPayload $payload, JsonApiAdapter $adapter)
    {
        $extracted = $this->extractPayload($payload);
        $this->validateExtracted($extracted);
        return $this->createResource($extracted, $adapter);
    }

    /**
     * Creates a Struct\Resource object from an extracted array payload.
     *
     * @param   array               $extracted
     * @param   AdapterInterface    $adapter
     * @return  Struct\Resource
     */
    protected function createResource(array $extracted, JsonApiAdapter $adapter)
    {
        $metadata = $this->extractMetadata($extracted, $adapter);
        $properties = $this->flattenExtracted($extracted, $metadata);
        // @todo flattenExtracted should be converted into three methods: extractId, extractType, and extractProperties
        $identifier = (isset($properties['id'])) ? $properties['id'] : null;
        if (isset($properties['id'])) {
            unset($properties['id']);
        }

        // @todo Should this be wrapped in an object, or is a hash fine?
        return [
            'id'            => $identifier,
            'type'          => $metadata->type,
            'properties'    => $properties,
        ];
    }

    /**
     * Takes an extracted array payload and flattens it to an array of key/values.
     *
     * @param   array           $extracted
     * @param   EntityMetadata  $metadata
     * @return  array
     * @throws  NormalizerException     On failed attempts to flatten the array.
     */
    abstract protected function flattenExtracted(array $extracted, EntityMetadata $metadata);

    /**
     * Extracts the EntityMetadata from the extracted array payload.
     *
     * @param   array               $extracted
     * @param   AdapterInterface    $adapter
     * @return  EntityMetadata
     */
    abstract protected function extractMetadata(array $extracted, JsonApiAdapter $adapter);

    /**
     * Extracts array data from a REST payload.
     *
     * @param   RestPayload     $payload
     * @return  array
     * @throws  NormalizerException
     */
    abstract protected function extractPayload(RestPayload $payload);

    /**
     * Validates an extract payload array.
     *
     * @param   array   $extracted
     * @return  array
     * @throws  NormalizerException
     */
    abstract protected function validateExtracted(array $extracted);
}
