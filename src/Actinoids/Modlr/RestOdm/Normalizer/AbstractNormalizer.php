<?php

namespace Actinoids\Modlr\RestOdm\Normalizer;

use Actinoids\Modlr\RestOdm\Struct;
use Actinoids\Modlr\RestOdm\Rest\RestPayload;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Adapter\AdapterInterface;
use Actinoids\Modlr\RestOdm\Hydrator\HydratorInterface;

/**
 * Abstract implementation of normalizing REST payloads into Struct\Resources.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class AbstractNormalizer implements NormalizerInterface
{
    /**
     * The Resource hydrator.
     * Used for normalizing incoming payloads into Struct\Resource objects.
     *
     * @var HydratorInterface
     */
    protected $hydrator;

    /**
     * Constructor.
     *
     * @param   HydratorInterface   $hydrator
     */
    public function __construct(HydratorInterface $hydrator)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize(RestPayload $payload, AdapterInterface $adapter)
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
    protected function createResource(array $extracted, AdapterInterface $adapter)
    {
        $metadata = $this->extractMetadata($extracted, $adapter);
        $flattened = $this->flattenExtracted($extracted, $metadata);
        return $this->hydrator->hydrateOne($metadata, null, $flattened);
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
    abstract protected function extractMetadata(array $extracted, AdapterInterface $adapter);

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
