<?php

namespace Actinoids\Modlr\RestOdm\Adapter;

use Actinoids\Modlr\RestOdm\Models\Model;
use Actinoids\Modlr\RestOdm\Models\Collection;
use Actinoids\Modlr\RestOdm\Store\Store;
use Actinoids\Modlr\RestOdm\Serializer\SerializerInterface;
use Actinoids\Modlr\RestOdm\Normalizer\NormalizerInterface;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Rest;

/**
 * Interface for handling API operations.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface AdapterInterface
{
    /**
     * Processes a REST request and formats them into REST responses.
     *
     * @param   Rest\RestRequest     $request
     * @return  Rest\RestResponse
     */
    public function processRequest(Rest\RestRequest $request);

    /**
     * Finds a single model by id.
     *
     * @param   string  $typeKey
     * @param   string  $identifier
     * @return  Rest\RestResponse
     */
    public function findRecord($typeKey, $identifier); //, array $fields = [], array $inclusions = []);

    /**
     * Finds a model (or models) for a model's related field.
     *
     * @param   string  $typeKey
     * @param   string  $identifier
     * @param   string  $fieldKey
     * @return  Rest\RestResponse
     * @throws  AdapterException    If the related field does not exist on the model.
     */
    public function findRelationship($typeKey, $identifier, $fieldKey);

    /**
     * Finds a multiple models by type.
     *
     * @param   string  $typeKey
     * @param   array   $identifiers
     * @return  Rest\RestResponse
     */
    public function findAll($typeKey, array $identifiers = []); //, array $pagination = [], array $fields = [], array $inclusions = [], array $sort = []);

    /**
     * Creates a new model.
     *
     * @param   string              $typeKey
     * @param   Rest\RestPayload    $payload
     * @return  Rest\RestResponse
     */
    public function createRecord($typeKey, Rest\RestPayload $payload); //, array $fields = [], array $inclusions = []);

    /**
     * Updates an existing model.
     *
     * @param   string              $typeKey
     * @param   string              $identifier
     * @param   Rest\RestPayload    $payload
     * @return  Rest\RestResponse
     */
    public function updateRecord($typeKey, $identifier, Rest\RestPayload $payload); // , array $fields = [], array $inclusions = []);

    /**
     * Deletes an existing model.
     *
     * @param   string              $typeKey
     * @param   string              $identifier
     * @return  Rest\RestResponse
     */
    public function deleteRecord($typeKey, $identifier);

    /**
     * Handles errors and returns an appropriate REST response.
     *
     * @param   \Exception  $e
     * @return  Rest\RestResponse
     */
    public function handleException(\Exception $e);

    /**
     * Builds a URL for an entity, or an entity relationship.
     *
     * @param   EntityMetadata  $metadata
     * @param   string          $identifier
     * @param   string|null     $externalRelKey
     * @param   bool            $isRelatedLink
     * @return  string
     */
    public function buildUrl(EntityMetadata $metadata, $identifier, $externalRelKey = null, $isRelatedLink = false);

    /**
     * Gets the metadata for a model type.
     *
     * @param   string  $typeKey
     * @return  EntityMetadata
     */
    public function getEntityMetadata($typeKey);

    /**
     * Gets the Store for handling persistence operations.
     *
     * @return  Store
     */
    public function getStore();

    /**
     * Gets the Serializer for serializing resources.
     *
     * @return  SerializerInterface
     */
    public function getSerializer();

    /**
     * Gets the Normalizer for normalizing REST payloads.
     *
     * @return  NormalizerInterface
     */
    public function getNormalizer();

    /**
     * Normalizes a Rest\RestPayload into an array record to apply to a Model.
     * Is used in conjunction with a SerializerInterface.
     *
     * @param   Rest\RestPayload    $payload
     * @return  array
     */
    public function normalize(Rest\RestPayload $payload);

    /**
     * Serializes a Model into a Rest\RestPayload object.
     * Is used in conjunction with a SerializerInterface.
     *
     * @param   Model|null  $model
     * @return  Rest\RestPayload
     */
    public function serialize(Model $model = null);

    /**
     * Serializes a Collection into a Rest\RestPayload object.
     * Is used in conjunction with a SerializerInterface.
     *
     * @param   Collection  $collection
     * @return  Rest\RestPayload
     */
    public function serializeCollection(Collection $collection);

    /**
     * Serializes an array of Models into a Rest\RestPayload object.
     * Is used in conjunction with a SerializerInterface.
     *
     * @param   Model[]     $models
     * @return  Rest\RestPayload
     */
    public function serializeArray(array $models);
}
