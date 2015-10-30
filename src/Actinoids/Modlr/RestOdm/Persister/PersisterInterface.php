<?php

namespace Actinoids\Modlr\RestOdm\Persister;

use Actinoids\Modlr\RestOdm\Models\Model;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;

/**
 * Defines the persister service implementation for persisting modals to a database layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface PersisterInterface
{
    /**
     * Gets all records for the specified type, optinally filtered by a set of identifiers.
     *
     * @todo    Implement sorting and pagination (limit/skip).
     * @param   EntityMetadata  $metadata
     * @param   array           $identifiers
     * @return  Record[]
     */
    public function all(EntityMetadata $metadata, array $identifiers = []);

    /**
     * Retrieves a single model record from the database.
     *
     * @param   EntityMetadata  $metadata   The metadata for the model/record.
     * @param   string          $identifier The identifier for the record. Always a string. The persister must convert.
     * @return  Record|null
     */
    public function retrieve(EntityMetadata $metadata, $identifier);

    /**
     * Creates a new database record from a model instance.
     *
     * @param   Model   $model
     * @return  Model
     */
    public function create(Model $model);

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
