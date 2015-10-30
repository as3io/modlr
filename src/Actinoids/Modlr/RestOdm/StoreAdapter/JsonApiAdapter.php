<?php

namespace Actinoids\Modlr\RestOdm\StoreAdapter;

use Actinoids\Modlr\RestOdm\Models\Model;
use Actinoids\Modlr\RestOdm\Models\Collection;
use Actinoids\Modlr\RestOdm\Exception\MetadataException;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Metadata\MetadataFactory;
use Actinoids\Modlr\RestOdm\Store\Store;
use Actinoids\Modlr\RestOdm\StoreSerializer\JsonApiSerializer;
use Actinoids\Modlr\RestOdm\StoreNormalizer\JsonApiNormalizer;
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
// class JsonApiAdapter implements AdapterInterface
class JsonApiAdapter
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
     * @var Store
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
    public function __construct(MetadataFactory $mf, JsonApiSerializer $serializer, JsonApiNormalizer $normalizer, Store $store, Rest\RestConfiguration $config)
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
     * "DONE"
     */
    public function findRecord($typeKey, $identifier) //, array $fields = [], array $inclusions = [])
    {
        $model = $this->getStore()->find($typeKey, $identifier);
        $payload = $this->serialize($model);
        return $this->createRestResponse(200, $payload);
    }

    /**
     * {@inheritDoc}
     * "DONE"
     */
    public function findAll($typeKey, array $identifiers = []) //, array $pagination = [], array $fields = [], array $inclusions = [], array $sort = [])
    {
        $collection = $this->getStore()->findAll($typeKey, $identifiers);
        $payload = $this->serializeCollection($collection);
        return $this->createRestResponse(200, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function createRecord($typeKey, Rest\RestPayload $payload) //, array $fields = [], array $inclusions = [])
    {
        $data = $this->normalize($payload);
        if (isset($data['id'])) {
            throw AdapterException::badRequest('An "id" member was found in the payload. Client-side ID generation is currently not supported.');
        }
        // @todo Does the type still need to be validated here? Yes, most likely - need to compare the endpoint versus the type.
        $model = $this->getStore()->create($data['type']);
        foreach ($data['properties'] as $key => $value) {
            $model->attribute($key, $value);
        }
        $model->save();
        $payload = $this->serialize($model);
        return $this->createRestResponse(201, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function updateRecord($typeKey, $identifier, Rest\RestPayload $payload) // , array $fields = [], array $inclusions = [])
    {
        $data = $this->normalize($payload);
        if (!isset($data['id'])) {
            throw AdapterException::badRequest('No "id" member was found in the payload.');
        }
        if ($identifier !== $data['id']) {
            throw AdapterException::badRequest('The request URI id does not match the payload id.');
        }
        $model = $this->getStore()->find($typeKey, $data['id']);
        foreach ($data['properties'] as $key => $value) {
            $model->attribute($key, $value);
        }
        $model->save();
        $payload = $this->serialize($model);
        return $this->createRestResponse(201, $payload);
    }

    public function deleteRecord($typeKey, $identifier)
    {
        $model = $this->getStore()->delete($typeKey, $identifier);
        return $this->createRestResponse(204);
    }

    /**
     * {@inheritDoc}
     */
    public function handleException(\Exception $e)
    {
        throw $e;
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
    public function serialize(Model $model)
    {
        return $this->getSerializer()->serialize($model, $this);
    }

    /**
     * {@inheritDoc}
     */
    public function serializeCollection(Collection $collection)
    {
        return $this->getSerializer()->serializeCollection($collection, $this);
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
    protected function createRestResponse($status, Rest\RestPayload $payload = null)
    {
        $restResponse = new Rest\RestResponse($status, $payload);
        $restResponse->addHeader('content-type', 'application/json');
        return $restResponse;
    }
}
