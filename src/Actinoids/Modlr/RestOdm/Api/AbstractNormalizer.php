<?php

namespace As3\Modlr\RestOdm\Api;

use As3\Modlr\RestOdm\Metadata\EntityMetadata;
use As3\Modlr\RestOdm\Rest\RestPayload;

/**
 * Abstract implementation of normalizing REST payloads into arrays that can be applied to a Model.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class AbstractNormalizer implements NormalizerInterface
{
    /**
     * {@inheritDoc}
     */
    public function normalize(RestPayload $payload, AdapterInterface $adapter)
    {
        $rawPayload = $this->getRawPayload($payload);
        $this->validateRawPayload($rawPayload);
        return $this->createNormalizedArray($rawPayload, $adapter);
    }

    /**
     * Creates a normalized array from an raw array payload.
     *
     * @param   array               $rawPayload
     * @param   AdapterInterface    $adapter
     * @return  array
     */
    protected function createNormalizedArray(array $rawPayload, AdapterInterface $adapter)
    {
        $metadata = $this->extractMetadata($rawPayload, $adapter);
        // @todo Should this be wrapped in an object, or is a hash fine?
        return [
            'id'            => $this->extractId($rawPayload),
            'type'          => $metadata->type,
            'properties'    => $this->extractProperties($rawPayload, $metadata)
        ];
    }

    /**
     * Extracts the EntityMetadata from the raw array payload.
     *
     * @param   array               $rawPayload
     * @param   AdapterInterface    $adapter
     * @return  EntityMetadata
     */
    protected function extractMetadata(array $rawPayload, AdapterInterface $adapter)
    {
        return $adapter->getEntityMetadata($this->extractType($rawPayload));
    }

    /**
     * Takes an raw array payload and extracts the model's unique id.
     *
     * @param   array           $rawPayload
     * @return  string|null
     * @throws  NormalizerException     On failed attempts to extract the id.
     */
    abstract protected function extractId(array $rawPayload);

    /**
     * Takes an raw array payload and extracts the model's type.
     *
     * @param   array           $rawPayload
     * @return  string
     * @throws  NormalizerException     On failed attempts to extract the model type.
     */
    abstract protected function extractType(array $rawPayload);

    /**
     * Takes an raw array payload and extracts the model properties (attributes and relationships) as a flattened, key/value array.
     *
     * @param   array           $rawPayload
     * @param   EntityMetadata  $metadata
     * @return  array
     * @throws  NormalizerException     On failed attempts to flatten the array.
     */
    abstract protected function extractProperties(array $rawPayload, EntityMetadata $metadata);

    /**
     * Extracts raw array data from a REST payload.
     *
     * @param   RestPayload     $payload
     * @return  array
     * @throws  NormalizerException
     */
    abstract protected function getRawPayload(RestPayload $payload);

    /**
     * Validates a the raw payload array.
     *
     * @param   array   $rawPayload
     * @return  array
     * @throws  NormalizerException
     */
    abstract protected function validateRawPayload(array $rawPayload);
}
