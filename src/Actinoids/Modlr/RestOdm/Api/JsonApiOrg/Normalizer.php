<?php

namespace Actinoids\Modlr\RestOdm\Api\JsonApiOrg;

use Actinoids\Modlr\RestOdm\Api\AbstractNormalizer;
use Actinoids\Modlr\RestOdm\Api\NormalizerException;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Rest\RestPayload;

/**
 * Normalizes REST payloads into standard arrays based on the JSON API spec.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Normalizer extends AbstractNormalizer
{
    /**
     * {@inheritDoc}
     */
    protected function extractId(array $rawPayload)
    {
        if (isset($rawPayload['data']['id']) && !empty($rawPayload['data']['id'])) {
            return $rawPayload['data']['id'];
        }
        return null;
    }

    /**
     * {@inheritDoc}
     */
    protected function extractType(array $rawPayload)
    {
        if (isset($rawPayload['data']['type']) && !empty($rawPayload['data']['type'])) {
            return $rawPayload['data']['type'];
        }
        throw NormalizerException::badRequest('The "type" member was missing from the payload. All payloads must contain a type.');
    }

    /**
     * {@inheritDoc}
     */
    protected function extractProperties(array $rawPayload, EntityMetadata $metadata)
    {
        $data = $rawPayload['data'];
        $attributes = $this->extractAttributes($data, $metadata);
        $relationships = $this->extractRelationships($data, $metadata);
        return array_merge($attributes, $relationships);
    }

    /**
     * Extracts the model's attributes, per JSON API spec.
     *
     * @param   array           $data
     * @param   EntityMetadata  $metadata
     * @return  array
     */
    protected function extractAttributes(array $data, EntityMetadata $metadata)
    {
        $flattened = [];
        if (!isset($data['attributes']) || !is_array($data['attributes'])) {
            return $flattened;
        }

        $keyMap = array_flip(array_keys($data['attributes']));
        foreach ($metadata->getAttributes() as $key => $attrMeta) {
            if (!isset($keyMap[$key])) {
                continue;
            }
            $flattened[$key] = $data['attributes'][$key];
        }
        return $flattened;
    }

    /**
     * Extracts the model's relationships, per JSON API spec.
     *
     * @param   array           $data
     * @param   EntityMetadata  $metadata
     * @return  array
     */
    protected function extractRelationships(array $data, EntityMetadata $metadata)
    {
        $flattened = [];
        if (!isset($data['relationships']) || !is_array($data['relationships'])) {
            return $flattened;
        }

        foreach ($metadata->getRelationships() as $key => $relMeta) {
            if (!isset($data['relationships'][$key])) {
                continue;
            }
            $rel = $data['relationships'][$key];
            // Need to use array_key_exists over isset because 'null' is valid. Creating a key map for each relationship is too costly since every relationship needs the data check.
            if (false === array_key_exists('data', $rel)) {
                throw NormalizerException::badRequest(sprintf('The "data" member was missing from the payload for relationship "%s"', $key));
            }

            if (empty($rel['data'])) {
                $flattened[$key] = $relMeta->isOne() ? null : [];
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
        return $flattened;
    }

    /**
     * {@inheritDoc}
     */
    protected function getRawPayload(RestPayload $payload)
    {
        $rawPayload = @json_decode($payload->getData(), true);
        if (!is_array($rawPayload)) {
            throw NormalizerException::badRequest('Unable to parse. Is the JSON valid?');
        }
        return $rawPayload;
    }

    /**
     * {@inheritDoc}
     */
    protected function validateRawPayload(array $rawPayload)
    {
        if (!isset($rawPayload['data']) || !is_array($rawPayload['data'])) {
            throw NormalizerException::badRequest('No "data" member was found in the payload. All payloads must be keyed with "data."');
        }

        if (empty($rawPayload['data'])) {
            return $rawPayload;
        }
        if (true === $this->isSequentialArray($rawPayload['data'])) {
            throw NormalizerException::badRequest('Normalizing multiple records is currently not supported.');
        }
        return $rawPayload;
    }

    /**
     * Determines if an array is sequential.
     *
     * @param   array   $arr
     * @return  bool
     */
    protected function isSequentialArray(array $arr)
    {
        if (empty($arr)) {
            return true;
        }
        return (range(0, count($arr) - 1) === array_keys($arr));
    }
}
