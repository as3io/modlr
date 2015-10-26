<?php

namespace Actinoids\Modlr\RestOdm\Adapter;

use Actinoids\Modlr\RestOdm\Exception\MetadataException;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Metadata\MetadataFactory;
use Actinoids\Modlr\RestOdm\Store\StoreInterface;
use Actinoids\Modlr\RestOdm\Serializer\JsonApiSerializer;
use Actinoids\Modlr\RestOdm\Normalizer\JsonApiNormalizer;
use Actinoids\Modlr\RestOdm\Rest;
use Actinoids\Modlr\RestOdm\Struct;
use Actinoids\Modlr\RestOdm\Util\Inflector;
use Actinoids\Modlr\RestOdm\Exception\HttpExceptionInterface;
use Actinoids\Modlr\RestOdm\Exception\InvalidArgumentException;

/**
 * Adapter for handling API operations using the JSON API specification.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class JsonApiAdapter implements AdapterInterface
{
    /**
     * The Modlr Metadata factory.
     *
     * @var MetadataFactory
     */
    private $mf;

    /**
     * The JsonApiSerializer
     *
     * @var JsonApiSerializer
     */
    private $serializer;

    /**
     * The JsonApiNormalizer
     *
     * @var JsonApiNormalizer
     */
    private $normalizer;

    /**
     * The Store to use for persistence operations.
     *
     * @var StoreInterface
     */
    private $store;

    /**
     * @var Inflector
     */
    private $inflector;

    /**
     * The REST configuration.
     *
     * @var Rest\RestConfiguration
     */
    private $config;

    /**
     * Constructor.
     *
     * @param   MetadataFactory         $mf
     * @param   JsonApiSerializer       $serializer
     * @param   JsonApiNormalizer       $normalizer
     * @param   StoreInterface          $store
     * @param   Rest\RestConfiguration  $config
     */
    public function __construct(MetadataFactory $mf, JsonApiSerializer $serializer, JsonApiNormalizer $normalizer, StoreInterface $store, Rest\RestConfiguration $config)
    {
        $this->mf = $mf;
        $this->serializer = $serializer;
        $this->normalizer = $normalizer;
        $this->store = $store;
        $this->config = $config;
        $this->inflector = new Inflector();
    }

    /**
     * {@inheritDoc}
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * {@inheritDoc}
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * {@inheritDoc}
     */
    public function getNormalizer()
    {
        return $this->normalizer;
    }

    /**
     * {@inheritDoc}
     */
    public function processRequest(Rest\RestRequest $request)
    {
        $internalType = $this->getInternalEntityType($request->getEntityType());
        $metadata = $this->getEntityMetadata($internalType);
        switch ($request->getMethod()) {
            case 'GET':
                if (true === $request->hasIdentifier()) {
                    if (false === $request->isRelationship() && false === $request->hasFilters()) {
                        return $this->findRecord($metadata, $request->getIdentifier(), $request->getFieldset(), $request->getInclusions());
                    }
                    throw AdapterException::badRequest('No GET handler found.');
                } else {
                    return $this->findMany($metadata, [], $request->getPagination(), $request->getFieldset(), $request->getInclusions(), $request->getSorting());
                }
                throw AdapterException::badRequest('No GET handler found.');
            case 'POST':
                // @todo Must validate JSON content type
                if (false === $request->hasIdentifier()) {
                    if (true === $request->hasPayload()) {
                        return $this->createRecord($metadata, $request->getPayload(), $request->getFieldset(), $request->getInclusions());
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
                return $this->updateRecord($metadata, $request->getIdentifier(), $request->getPayload(), $request->getFieldset(), $request->getInclusions());
            case 'DELETE':
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
    public function findRecord(EntityMetadata $metadata, $identifier, array $fields = [], array $inclusions = [])
    {
        $resource = $this->getStore()->findRecord($metadata, $identifier, $fields, $inclusions);
        $payload = $this->serialize($resource);
        return $this->createRestResponse(200, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function findMany(EntityMetadata $metadata, array $identifiers = [], array $pagination = [], array $fields = [], array $inclusions = [], array $sort = [])
    {
        $resource = $this->getStore()->findMany($metadata, $identifiers, $pagination, $fields, $inclusions, $sort);
        $payload = $this->serialize($resource);
        return $this->createRestResponse(200, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function createRecord(EntityMetadata $metadata, Rest\RestPayload $payload, array $fields = [], array $inclusions = [])
    {
        $resource = $this->normalize($payload);
        if (true === $resource->isMany()) {
            throw AdapterException::badRequest('Multiple records were found in the payload. Batch creation is currently not supported.');
        }
        if (false === $resource->getPrimaryData()->isNew()) {
            throw AdapterException::badRequest('An "id" member was found in the payload. Client-side ID generation is currently not supported.');
        }
        try {
            $this->mf->validateResourceTypes($metadata->type, $resource->getEntityType());
        } catch (InvalidArgumentException $e) {
            throw AdapterException::badRequest($e->getMessage());
        }
        $resource = $this->getStore()->createRecord($metadata, $resource, $fields, $inclusions);
        $payload = $this->serialize($resource);
        return $this->createRestResponse(201, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function updateRecord(EntityMetadata $metadata, $identifier, Rest\RestPayload $payload, array $fields = [], array $inclusions = [])
    {
        $resource = $this->normalize($payload);
        if (true === $resource->isMany()) {
            throw AdapterException::badRequest('Multiple records were found in the payload. Batch creation is currently not supported.');
        }
        if (true === $resource->getPrimaryData()->isNew()) {
            throw AdapterException::badRequest('No "id" member was found in the payload.');
        }

        if ($identifier !== $resource->getPrimaryData()->getId()) {
            throw AdapterException::badRequest(sprintf('The identifiers are mismatched. Expected "id" member value to be "%s"', $identifier));

        }
        try {
            $this->mf->validateResourceTypes($metadata->type, $resource->getEntityType());
        } catch (InvalidArgumentException $e) {
            throw AdapterException::badRequest($e->getMessage());
        }
        $resource = $this->getStore()->updateRecord($metadata, $resource, $fields, $inclusions);
        $payload = $this->serialize($resource);
        return $this->createRestResponse(201, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function handleException(\Exception $e)
    {
        $refl = new \ReflectionClass($e);
        if ($e instanceof HttpExceptionInterface) {
            $title  = sprintf('%s::%s', $refl->getShortName(), $e->getErrorType());
            $detail = $e->getMessage();
            $status = $e->getHttpCode();
        } else {
            $title  = $refl->getShortName();
            $detail = 'An internal server error occured';
            $status = 500;
        }

        $serialized = $this->getSerializer()->serializeError($title, $detail, $status);
        $payload = new Rest\RestPayload($serialized);
        return $this->createRestResponse($status, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function getInternalEntityType($externalType)
    {
        return $externalType;
        $parts = explode($this->config->getExternalNamespaceDelim(), $externalType);
        foreach ($parts as &$part) {
            $part = $this->inflector->studlify($part);
        }
        return implode($this->config->getInternalNamespaceDelim(), $parts);
    }

    /**
     * {@inheritDoc}
     */
    public function getExternalEntityType($internalType)
    {
        return $internalType;
        $parts = explode($this->config->getInternalNamespaceDelim(), $internalType);
        foreach ($parts as &$part) {
            $part = $this->inflector->dasherize($part);
        }
        return implode($this->config->getExternalNamespaceDelim(), $parts);
    }

    /**
     * {@inheritDoc}
     */
    public function getExternalFieldKey($internalKey)
    {
        return $this->inflector->dasherize($internalKey);
    }

    /**
     * {@inheritDoc}
     */
    public function buildUrl(EntityMetadata $metadata, $identifier, $relFieldKey = null, $isRelatedLink = false)
    {
        $externalType = $this->getExternalEntityType($metadata->type);

        $url = sprintf('%s://%s%s/%s/%s',
            $this->config->getScheme(),
            $this->config->getHost(),
            $this->config->getRootEndpoint(),
            $this->getExternalEntityType($metadata->type),
            $identifier
        );

        if (null !== $relFieldKey) {
            if (false === $isRelatedLink) {
                $url .= '/relationships';
            }
            $url .= sprintf('/%s', $this->getExternalFieldKey($relFieldKey));
        }
        return $url;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize(Rest\RestPayload $payload)
    {
        return $this->getNormalizer()->normalize($payload, $this);
    }

    /**
     * {@inheritDoc}
     */
    public function serialize(Struct\Resource $resource)
    {
        return $this->getSerializer()->serialize($resource, $this);
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityMetadata($internalType)
    {
        try {
            return $this->mf->getMetadataForType($internalType);
        } catch (MetadataException $e) {
            if (100 === $e->getCode()) {
                throw AdapterException::entityTypeNotFound($internalType);
            }
            throw $e;
        }
    }

    /**
     * Creates a RestResponse object based on common response parameters shared by this adapter.
     *
     * @param   int                 $status
     * @param   Rest\RestPayload    $payload
     * @return  Rest\RestResponse
     */
    protected function createRestResponse($status, Rest\RestPayload $payload)
    {
        $restResponse = new Rest\RestResponse($status, $payload);
        $restResponse->addHeader('content-type', 'application/json');
        return $restResponse;
    }
}
