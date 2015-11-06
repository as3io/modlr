<?php

namespace Actinoids\Modlr\RestOdm\Adapter;

use Actinoids\Modlr\RestOdm\Models\Model;
use Actinoids\Modlr\RestOdm\Models\Collection;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Store\Store;
use Actinoids\Modlr\RestOdm\Serializer\SerializerInterface;
use Actinoids\Modlr\RestOdm\Normalizer\NormalizerInterface;
use Actinoids\Modlr\RestOdm\Rest;
use Actinoids\Modlr\RestOdm\Exception\HttpExceptionInterface;

/**
 * Abstract Adapter implementation for handling API operations.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * The Serializer
     *
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * The Normalizer
     *
     * @var NormalizerInterface
     */
    protected $normalizer;

    /**
     * The Store to use for persistence operations.
     *
     * @var Store
     */
    protected $store;

    /**
     * The REST configuration.
     *
     * @var Rest\RestConfiguration
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param   SerializerInterface     $serializer
     * @param   NormalizerInterface     $normalizer
     * @param   StoreInterface          $store
     * @param   Rest\RestConfiguration  $config
     */
    public function __construct(SerializerInterface $serializer, NormalizerInterface $normalizer, Store $store, Rest\RestConfiguration $config)
    {
        $this->serializer = $serializer;
        $this->normalizer = $normalizer;
        $this->store = $store;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function processRequest(Rest\RestRequest $request);

    /**
     * {@inheritDoc}
     */
    public function findRecord($typeKey, $identifier) //, array $fields = [], array $inclusions = [])
    {
        $model = $this->getStore()->find($typeKey, $identifier);
        $payload = $this->serialize($model);
        return $this->createRestResponse(200, $payload);
    }

    /**
     * {@inheritDoc}
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
    public function findRelationship($typeKey, $identifier, $fieldKey)
    {
        $model = $this->getStore()->find($typeKey, $identifier);
        if (false === $model->isRelationship($fieldKey)) {
            throw AdapterException::badRequest(sprintf('The relationship field "%s" does not exist on model "%s"', $fieldKey, $typeKey));
        }
        $rel = $model->get($fieldKey);
        $payload = (true === $model->isHasOne($fieldKey)) ? $this->serialize($rel) : $this->serializeArray($rel);
        return $this->createRestResponse(200, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function createRecord($typeKey, Rest\RestPayload $payload) //, array $fields = [], array $inclusions = [])
    {
        // @todo Do normalized payloads need to be wrapped in an object, similar to persistence Records?
        $normalized = $this->normalize($payload);
        $this->validateCreatePayload($typeKey, $normalized);

        $model = $this->getStore()->create($normalized['type']);
        $model->apply($normalized['properties']);
        $model->save();
        $payload = $this->serialize($model);
        return $this->createRestResponse(201, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function updateRecord($typeKey, $identifier, Rest\RestPayload $payload) // , array $fields = [], array $inclusions = [])
    {
        $normalized = $this->normalize($payload);
        $this->validateUpdatePayload($typeKey, $identifier, $normalized);

        $model = $this->getStore()->find($typeKey, $normalized['id']);
        $model->apply($normalized['properties']);
        $model->save();
        $payload = $this->serialize($model);
        return $this->createRestResponse(200, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteRecord($typeKey, $identifier)
    {
        $this->getStore()->delete($typeKey, $identifier);
        return $this->createRestResponse(204);
    }

    /**
     * {@inheritDoc}
     */
    abstract public function buildUrl(EntityMetadata $metadata, $identifier, $relFieldKey = null, $isRelatedLink = false);

    /**
     * Validates a normalized create record payload.
     *
     * @param   string  $typeKey
     * @param   array   $normalized
     * @throws  AdapterException    On invalid create record payload.
     */
    abstract protected function validateCreatePayload($typeKey, array $normalized);

    /**
     * Validates a normalized update record payload.
     *
     * @param   string  $typeKey
     * @param   string  $identifier
     * @param   array   $normalized
     * @throws  AdapterException    On invalid update record payload.
     */
    abstract protected function validateUpdatePayload($typeKey, $identifier, array $normalized);

    /**
     * Creates a RestResponse object based on common response parameters shared by this adapter.
     *
     * @param   int                 $status
     * @param   Rest\RestPayload    $payload
     * @return  Rest\RestResponse
     */
    abstract protected function createRestResponse($status, Rest\RestPayload $payload = null);

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
            $detail = 'Oh no! Something bad happened on the server! Please try again.';
            $status = 500;
        }

        $serialized = $this->getSerializer()->serializeError($title, $detail, $status);
        $payload = new Rest\RestPayload($serialized);
        return $this->createRestResponse($status, $payload);
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
    public function normalize(Rest\RestPayload $payload)
    {
        return $this->getNormalizer()->normalize($payload, $this);
    }

    /**
     * {@inheritDoc}
     */
    public function serialize(Model $model = null)
    {
        return new Rest\RestPayload($this->getSerializer()->serialize($model, $this));
    }

    /**
     * {@inheritDoc}
     */
    public function serializeArray(array $models)
    {
        return new Rest\RestPayload($this->getSerializer()->serializeArray($models, $this));
    }

    /**
     * {@inheritDoc}
     */
    public function serializeCollection(Collection $collection)
    {
        return new Rest\RestPayload($this->getSerializer()->serializeCollection($collection, $this));
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityMetadata($typeKey)
    {
        return $this->getStore()->getMetadataForType($typeKey);
    }
}
