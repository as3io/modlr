<?php

namespace Actinoids\Modlr\RestOdm\Adapter;

use Actinoids\Modlr\RestOdm\Models\Model;
use Actinoids\Modlr\RestOdm\Models\Collection;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Store\Store;
use Actinoids\Modlr\RestOdm\Serializer\JsonApiSerializer;
use Actinoids\Modlr\RestOdm\Normalizer\JsonApiNormalizer;
use Actinoids\Modlr\RestOdm\Rest;
use Actinoids\Modlr\RestOdm\Exception\HttpExceptionInterface;

/**
 * Adapter for handling API operations using the JSON API specification.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class JsonApiAdapter extends AbstractAdapter
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
                throw AdapterException::invalidRequestMethod($request->getMethod());
        }
        var_dump(__METHOD__, $request);
        die();
    }

    /**
     * {@inheritDoc}
     */
    protected function validateCreatePayload($typeKey, array $normalized)
    {
        // @todo Does the type still need to be validated here? Yes, most likely - need to compare the endpoint versus the type.
        if (isset($normalized['id'])) {
            throw AdapterException::badRequest('An "id" member was found in the payload. Client-side ID generation is currently not supported.');
        }
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
        return $normalized;
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
