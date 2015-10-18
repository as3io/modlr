<?php

namespace Actinoids\Modlr\RestOdm\Adapter;

use Actinoids\Modlr\RestOdm\Store\StoreInterface;
use Actinoids\Modlr\RestOdm\Serializer\SerializerInterface;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Rest;
use Actinoids\Modlr\RestOdm\Struct;

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
     * Finds a single entity by id.
     *
     * @param   EntityMetadata  $metadata
     * @param   string          $identifier
     * @param   array           $fields
     * @param   array           $inclusions
     * @return  Rest\RestPayload
     */
    public function findRecord(EntityMetadata $metadata, $identifier, array $fields = [], array $inclusions = []);

    /**
     * Finds a multiple entities by type.
     *
     * @param   EntityMetadata  $metadata
     * @param   array           $identifiers
     * @param   array           $pagination
     * @param   array           $fields
     * @param   array           $inclusions
     * @param   array           $sort
     * @return  Rest\RestPayload
     */
    public function findMany(EntityMetadata $metadata, array $identifiers = [], array $pagination = [], array $fields = [], array $inclusions = [], array $sort = []);

    /**
     * Handles errors and returns an appropriate REST response.
     *
     * @param   \Exception  $e
     * @return  Rest\RestResponse
     */
    public function handleException(\Exception $e);

    /**
     * Gets the internal Modlr entity type, based on this adapter's external type.
     *
     * @param   string  $externalType
     * @return  string
     */
    public function getInternalEntityType($externalType);

    /**
     * Gets the external adapter entity type, based on the internal Modlr type.
     *
     * @param   string  $internalType
     * @return  string
     */
    public function getExternalEntityType($internalType);

    /**
     * Gets the external adapter field key, based on the internal Modlr field key.
     *
     * @param   string  $internalKey
     * @return  string
     */
    public function getExternalFieldKey($internalKey);

    /**
     * Gets the Store for handling persistence operations.
     *
     * @return  StoreInterface
     */
    public function getStore();

    /**
     * Gets the Serializer for serializing and normalizing REST payloads.
     *
     * @return  SerializerInterface
     */
    public function getSerializer();

    /**
     * Gets entity metadata, based on entity type.
     *
     * @param   string  $type
     * @return  EntityMetadata
     */
    public function getEntityMetadata($type);

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
     * Normalizes a Rest\RestPayload into a Struct\Resource object.
     * Is used in conjunction with a SerializerInterface.
     *
     * @param   Rest\RestPayload    $payload
     * @return  Struct\Resource
     */
    public function normalize(Rest\RestPayload $payload);

    /**
     * Serializes a Struct\Resource into a Rest\RestPayload object.
     * Is used in conjunction with a SerializerInterface.
     *
     * @param   Struct\Resource     $resource
     * @return  Rest\RestPayload
     */
    public function serialize(Struct\Resource $resource);
}
