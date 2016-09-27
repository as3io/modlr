<?php

namespace As3\Modlr\Persister;

use As3\Modlr\Store\Store;
use As3\Modlr\Models\Model;
use As3\Modlr\Metadata\EntityMetadata;
use As3\Modlr\Metadata\Interfaces\PersistenceMetadataFactoryInterface;

/**
 * Defines the persister service implementation for persisting models to a data layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface PersisterInterface
{
    /**
     * Gets the key name for this persister.
     * Is used to uniquely indentify this persistence type by the persister manager.
     * Is also the type key for the persistence metadata layer.
     *
     * @return  string
     */
    public function getPersisterKey();

    /**
     * Gets the persistence metadata factory for creating PersistenceInterface instances.
     *
     * @return  PersistenceMetadataFactoryInterface
     */
    public function getPersistenceMetadataFactory();

    /**
     * Gets all records for the specified type, optinally filtered by a set of identifiers.
     *
     * @todo    Implement sorting and pagination (limit/skip).
     * @param   EntityMetadata  $metadata
     * @param   Store           $store
     * @param   array           $identifiers
     * @param   array           $fields
     * @param   array           $sort
     * @param   int             $offset
     * @param   int             $limit
     * @return  RecordSetInterface
     */
    public function all(EntityMetadata $metadata, Store $store, array $identifiers = [], array $fields = [], array $sort = [], $offset = 0, $limit = 0);

    /**
     * Retrieves a single model record from the database.
     *
     * @param   EntityMetadata  $metadata   The metadata for the model/record.
     * @param   string          $identifier The identifier for the record. Always a string. The persister must convert.
     * @param   Store           $store
     * @return  RecordSetInterface
     */
    public function retrieve(EntityMetadata $metadata, $identifier, Store $store);

    /**
     * Queries for multiple records based on a provided set of criteria.
     *
     * @param   EntityMetadata      $metadata   The metadata for the model.
     * @param   Store               $store      The model store.
     * @param   array               $criteria   The query criteria.
     * @param   array               $fields     Fields to include/exclude.
     * @param   array               $sort       The sort criteria.
     * @param   int                 $offset     The starting offset, aka the number of Models to skip.
     * @param   int                 $limit      The number of Models to limit.
     * @return  RecordSetInterface
     */
    public function query(EntityMetadata $metadata, Store $store, array $criteria, array $fields = [], array $sort = [], $offset = 0, $limit = 0);

    /**
     * Retrieves inverse record references for the provided owner, based on the related metadata, identifiers, and field.
     *
     * @param   EntityMetadata  $owner      The metadata for the owning model.
     * @param   EntityMetadata  $rel        The metadata for the related model.
     * @param   Store           $store
     * @param   string          $inverseField   The field key to query.
     * @param   array           $identifiers    The identifiers to query.
     * @return  RecordSetInterface
     */
    public function inverse(EntityMetadata $owner, EntityMetadata $rel, Store $store, array $identifiers, $inverseField);

    /**
     * Creates a new database record from a model instance.
     *
     * @param   Model   $model
     * @return  Model
     */
    public function create(Model $model);

    /**
     * Applies configured schemata to the underlying storage layer
     *
     * @param   EntityMetadata   $metadata
     */
    public function createSchemata(EntityMetadata $metadata);

    /**
     * Re-applies configured schemata to the underlying storage layer
     *
     * @param   EntityMetadata   $metadata
     */
    public function syncSchemata(EntityMetadata $metadata);

    /**
     * Updates an existing database record from a model instance.
     *
     * @param   Model   $model
     * @return  Model
     */
    public function update(Model $model);

    /**
     * Deletes a database record.
     *
     * @param   Model   $model
     * @return  Model
     */
    public function delete(Model $model);

    /**
     * Gets the identifier field key name for this database.
     *
     * @return  string
     */
    public function getIdentifierKey();

    /**
     * Gets the polymorphic field key name for this database.
     *
     * @return  string
     */
    public function getPolymorphicKey();

    /**
     * Extracts the model type based on raw database data.
     * Is needed to determine the proper polymorphic type, where applicable.
     *
     * @param   EntityMetadata  $metadata
     * @param   array           $data
     * @return  string
     * @throws  PersisterException If the type cannot be extracted/found on the raw record data.
     */
    public function extractType(EntityMetadata $metadata, array $data);

    /**
     * Generates a new persistence level identifier based on the (optional) generation strategy.
     *
     * @param   string|null     $strategy   The generation strategy, if provided.
     * @return  mixed
     */
    public function generateId($strategy = null);

    /**
     * Converts a stringified store level identifier into a persistence level identifier.
     * Is based on the (optional) generation strategy.
     *
     * @param   string      $identifier The stringified identifier to convert.
     * @param   string|null $strategy   The generation strategy, if provided.
     * @return  mixed
     */
    public function convertId($identifier, $strategy = null);
}
