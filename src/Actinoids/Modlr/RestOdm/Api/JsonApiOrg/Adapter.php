<?php

namespace Actinoids\Modlr\RestOdm\Api\JsonApiOrg;

use Actinoids\Modlr\RestOdm\Api\AbstractAdapter;
use Actinoids\Modlr\RestOdm\Api\AdapterException;
use Actinoids\Modlr\RestOdm\Exception\HttpExceptionInterface;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Models\Collection;
use Actinoids\Modlr\RestOdm\Models\Model;
use Actinoids\Modlr\RestOdm\Rest;
use Actinoids\Modlr\RestOdm\Store\Store;

/**
 * Adapter for handling API operations using the JSON API specification.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
final class Adapter extends AbstractAdapter
{
    /**
     * {@inheritDoc}
     */
    public function processRequest(Rest\RestRequest $request)
    {
        switch ($request->getMethod()) {
            case 'GET':
                if (true === $request->hasIdentifier()) {
                    if (false === $request->isRelationship() && false === $request->hasFilters()) {
                        return $this->findRecord($request->getEntityType(), $request->getIdentifier());
                    }
                    if (true === $request->isRelationshipRetrieve()) {
                        return $this->findRelationship($request->getEntityType(), $request->getIdentifier(), $request->getRelationshipFieldKey());
                    }
                    throw AdapterException::badRequest('No GET handler found.');
                } else {
                    return $this->findAll($request->getEntityType(), []); //, $request->getPagination(), $request->getFieldset(), $request->getInclusions(), $request->getSorting());
                }
                throw AdapterException::badRequest('No GET handler found.');
            case 'POST':
                // @todo Must validate JSON content type
                if (false === $request->hasIdentifier()) {
                    if (true === $request->hasPayload()) {
                        return $this->createRecord($request->getEntityType(), $request->getPayload(), $request->getFieldset(), $request->getInclusions());
                    }
                    throw AdapterException::requestPayloadNotFound('Unable to create new entity.');
                }
                throw AdapterException::badRequest('Creating a new record while providing an id is not supported.');
            case 'PATCH':
                // @todo Must validate JSON content type
                if (false === $request->hasIdentifier()) {
                    throw AdapterException::badRequest('No identifier found. You must specify an ID in the URL.');
                }
                if (false === $request->hasPayload()) {
                    throw AdapterException::requestPayloadNotFound('Unable to update entity.');
                }
                return $this->updateRecord($request->getEntityType(), $request->getIdentifier(), $request->getPayload(), $request->getFieldset(), $request->getInclusions());
            case 'DELETE':
                if (false === $request->hasIdentifier()) {
                    throw AdapterException::badRequest('No identifier found. You must specify an ID in the URL.');
                }
                return $this->deleteRecord($request->getEntityType(), $request->getIdentifier());
                throw AdapterException::badRequest('No DELETE request handler found.');
            default:
                throw AdapterException::badRequest(sprintf('The request method "%s" is not supported.', $request->getMethod()));
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function validateCreatePayload($typeKey, array $normalized)
    {
        if (isset($normalized['id'])) {
            throw AdapterException::badRequest('An "id" member was found in the payload. Client-side ID generation is currently not supported.');
        }

        if (!isset($normalized['type'])) {
            throw AdapterException::badRequest('An "type" member was found in the payload. All creation payloads must contain the model type.');
        }

        $this->validatePayloadType($normalized['type'], $typeKey);
        return $normalized;
    }

    /**
     * {@inheritDoc}
     */
    protected function validateUpdatePayload($typeKey, $identifier, array $normalized)
    {
        // @todo Does the type still need to be validated here? Yes, most likely - need to compare the endpoint versus the type.
        if (!isset($normalized['id'])) {
            throw AdapterException::badRequest('No "id" member was found in the payload.');
        }
        if ($identifier !== $normalized['id']) {
            throw AdapterException::badRequest('The request URI id does not match the payload id.');
        }
        if (!isset($normalized['type'])) {
            throw AdapterException::badRequest('An "type" member was found in the payload. All creation payloads must contain the model type.');
        }
        $this->validatePayloadType($normalized['type'], $typeKey);

        return $normalized;
    }

    /**
     * Validates that the payload type key matches the endpoint type key.
     * Also handles proper type keys for polymorphic endpoints.
     *
     * @param   string  $payloadTypeKey
     * @param   string  $endpointTypeKey
     * @throws  AdpaterException
     */
    protected function validatePayloadType($payloadTypeKey, $endpointTypeKey)
    {
        $metadata = $this->getStore()->getMetadataForType($endpointTypeKey);

        if (false === $metadata->isPolymorphic() && $payloadTypeKey === $endpointTypeKey) {
            return;
        }

        if (true === $metadata->isPolymorphic() && in_array($payloadTypeKey, $metadata->ownedTypes)) {
            return;
        }
        $expected = (true === $metadata->isPolymorphic()) ? implode(', ', $metadata->ownedTypes) : $endpointTypeKey;
        throw AdapterException::badRequest(sprintf('The payload "type" member does not match the API endpoint. Expected one of "%s" but received "%s"', $expected, $payloadTypeKey));
    }

    /**
     * {@inheritDoc}
     */
    public function buildUrl(EntityMetadata $metadata, $identifier, $relFieldKey = null, $isRelatedLink = false)
    {
        $url = sprintf('%s://%s%s/%s/%s',
            $this->config->getScheme(),
            $this->config->getHost(),
            $this->config->getRootEndpoint(),
            $metadata->type,
            $identifier
        );

        if (null !== $relFieldKey) {
            if (false === $isRelatedLink) {
                $url .= '/relationships';
            }
            $url .= sprintf('/%s', $relFieldKey);
        }
        return $url;
    }

    /**
     * {@inheritDoc}
     */
    protected function createRestResponse($status, Rest\RestPayload $payload = null)
    {
        $restResponse = new Rest\RestResponse($status, $payload);
        $restResponse->addHeader('content-type', 'application/json');
        return $restResponse;
    }
}
