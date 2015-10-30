<?php

namespace Actinoids\Modlr\RestOdm\StoreNormalizer;

use Actinoids\Modlr\RestOdm\Struct;
use Actinoids\Modlr\RestOdm\Rest\RestPayload;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\StoreAdapter\JsonApiAdapter;

/**
 * Normalizes REST payloads in Struct\Resources based on the JSON API spec.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class JsonApiNormalizer extends AbstractNormalizer
{
    /**
     * {@inheritDoc}
     */
    protected function flattenExtracted(array $extracted, EntityMetadata $metadata)
    {
        $data = $extracted['data'];

        $flattened = [];

        if (isset($data['id']) && !empty($data['id'])) {
            $flattened['id'] = $data['id'];
        }

        if (isset($data['attributes']) && is_array($data['attributes'])) {
            foreach ($metadata->getAttributes() as $key => $attrMeta) {
                if (false === array_key_exists($key, $data['attributes'])) {
                    continue;
                }
                $flattened[$key] = $data['attributes'][$key];
            }
        }

        if (isset($data['relationships']) && is_array($data['relationships'])) {
            foreach ($metadata->getRelationships() as $key => $relMeta) {
                if (false === array_key_exists($key, $data['relationships'])) {
                    continue;
                }
                $rel = $data['relationships'][$key];
                if (false === array_key_exists('data', $rel)) {
                    throw NormalizerException::badRequest(sprintf('The "data" member was missing from the payload for relationship "%s"', $key));
                }

                if (empty($rel['data'])) {
                    $flattened[$key] = null;
                    continue;
                }

                if (!is_array($rel['data'])) {
                    throw NormalizerException::badRequest(sprintf('The "data" member is not valid in the payload for relationship "%s"', $key));
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
        return $flattened;
    }

    /**
     * {@inheritDoc}
     */
    protected function extractMetadata(array $extracted, JsonApiAdapter $adapter)
    {
        return $adapter->getEntityMetadata($extracted['data']['type']);
    }

    /**
     * {@inheritDoc}
     */
    protected function extractPayload(RestPayload $payload)
    {
        $extracted = @json_decode($payload->getData(), true);
        if (!is_array($extracted)) {
            throw NormalizerException::badRequest('Unable to parse. Is the JSON valid?');
        }
        return $extracted;
    }

    /**
     * {@inheritDoc}
     */
    protected function validateExtracted(array $extracted)
    {
        if (!isset($extracted['data']) || !is_array($extracted['data'])) {
            throw NormalizerException::badRequest('No "data" member was found in the payload. All payloads must be keyed with "data."');
        }

        if (true === $this->isSequentialArray($extracted['data'])) {
            throw NormalizerException::badRequest('Normalizing multiple records is currently not supported.');
        }

        if (!isset($extracted['data']['type'])) {
            throw NormalizerException::badRequest('The "type" member was missing from the payload. All payloads must contain a type.');
        }
        return $extracted;
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
