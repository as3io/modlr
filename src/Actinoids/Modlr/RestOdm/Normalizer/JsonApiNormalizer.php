<?php

namespace Actinoids\Modlr\RestOdm\Normalizer;

use Actinoids\Modlr\RestOdm\Rest\RestPayload;
use Actinoids\Modlr\RestOdm\Adapter\AdapterInterface;
use Actinoids\Modlr\RestOdm\Hydrator\JsonApiHydrator;

class JsonApiNormalizer implements NormalizerInterface
{
    /**
     * The Resource hydrator.
     * Used for normalizing incoming payloads into Struct\Resource objects.
     *
     * @var JsonApiHydrator
     */
    private $hydrator;

    /**
     * Constructor.
     *
     * @param   TypeFactory     $typeFactory
     */
    public function __construct(JsonApiHydrator $hydrator)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize(RestPayload $payload, AdapterInterface $adapter)
    {
        $data = @json_decode($payload->getData(), true);
        if (!is_array($data)) {
            throw NormalizerException::badRequest('Unable to parse. Is the JSON valid?');
        }
        if (!isset($data['data'])) {
            throw NormalizerException::badRequest('No "data" member was found in the payload. All payloads must be keyed with "data."');
        }

        $data = $data['data'];
        if (true === $this->isSequentialArray($data)) {
            throw NormalizerException::badRequest('Normalizing multiple records is currently not supported.');
        }

        if (!isset($data['type'])) {
            throw NormalizerException::badRequest('The "type" member was missing from the payload. All payloads must contain a type.');
        }

        $metadata = $adapter->getEntityMetadata($data['type']);
        $flattened['type'] = $data['type'];
        if (isset($data['attributes']) && is_array($data['attributes'])) {
            foreach ($metadata->getAttributes() as $key => $attrMeta) {
                if (!isset($data['attributes'][$key])) {
                    continue;
                }
                $flattened[$key] = $data['attributes'][$key];
            }
        }

        if (isset($data['relationships']) && is_array($data['relationships'])) {
            foreach ($metadata->getRelationships() as $key => $relMeta) {
                if (!isset($data['relationships'][$key])) {
                    continue;
                }
                $rel = $data['relationships'][$key];
                if (!is_array($rel) || !isset($rel['data'])) {
                    throw NormalizerException::badRequest(sprintf('The "data" member was missing from the payload for relationship "%s"', $key));
                }
                if (true === $relMeta->isOne() && true === $this->isSequentialArray($rel['data'])) {
                    throw NormalizerException::badRequest(sprintf('The data payload for relationship "%s" is malformed. Data types of "one" must be an associative array, sequential found.', $key));
                }
                if (true === $relMeta->isMany() && false === $this->isSequentialArray($rel['data'])) {
                    throw NormalizerException::badRequest(sprintf('The data payload for relationship "%s" is malformed. Data types of "many" must be a sequential array, associative found.', $key));
                }
                $flattened[$key] = $rel['data'];
            }
        }
        return $this->hydrator->hydrateOne($metadata, null, $flattened);
    }

    /**
     * Determines if an array is sequential.
     *
     * @param   array   $arr
     * @return  bool
     */
    protected function isSequentialArray(array $arr)
    {
        return (range(0, count($arr) - 1) === array_keys($arr));
    }
}
