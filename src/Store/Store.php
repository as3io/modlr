<?php

namespace As3\Modlr\Store;

use As3\Modlr\DataTypes\TypeFactory;
use As3\Modlr\Events\EventDispatcher;
use As3\Modlr\Metadata\EmbeddedPropMetadata;
use As3\Modlr\Metadata\EmbedMetadata;
use As3\Modlr\Metadata\EntityMetadata;
use As3\Modlr\Metadata\MetadataFactory;
use As3\Modlr\Metadata\RelationshipMetadata;
use As3\Modlr\Models\Collections;
use As3\Modlr\Models\Embed;
use As3\Modlr\Models\Model;
use As3\Modlr\Persister\PersisterInterface;
use As3\Modlr\Persister\RecordSetInterface;
use As3\Modlr\StorageLayerManager;
use As3\Modlr\Store\Events\ModelLifecycleArguments;
use As3\Modlr\Store\Events\PreQueryArguments;

/**
 * Manages models and their persistence.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Store
{
    /**
     * @var MetadataFactory
     */
    private $mf;

    /**
     * @var TypeFactory
     */
    private $typeFactory;

    /**
     * The storage layer  manager.
     * Retrieves the appropriate persister and search client for handling records.
     *
     * @var  StorageLayerManager
     */
    private $storageManager;

    /**
     * Contains all models currently loaded in memory.
     *
     * @var Cache
     */
    private $cache;

    /**
     * The event dispatcher for firing model lifecycle events.
     *
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * Constructor.
     *
     * @param   MetadataFactory         $mf
     * @param   StorageLayerManager     $storageManager
     * @param   TypeFactory             $typeFactory
     * @param   EventDispatcher         $dispatcher
     */
    public function __construct(MetadataFactory $mf, StorageLayerManager $storageManager, TypeFactory $typeFactory, EventDispatcher $dispatcher)
    {
        $this->mf = $mf;
        $this->storageManager = $storageManager;
        $this->typeFactory = $typeFactory;
        $this->dispatcher = $dispatcher;
        $this->cache = new Cache();
    }

    /**
     * Finds a single record from the persistence layer, by type and id.
     * Will return a Model object if found, or throw an exception if not.
     *
     * @api
     * @param   string  $typeKey    The model type.
     * @param   string  $identifier The model id.
     * @return  Model
     */
    public function find($typeKey, $identifier)
    {
        if (true === $this->cache->has($typeKey, $identifier)) {
            return $this->cache->get($typeKey, $identifier);
        }
        $record = $this->retrieveRecord($typeKey, $identifier);
        return $this->loadModel($typeKey, $record);
    }

    /**
     * Returns the available type keys from the MetadataFactory
     *
     * @return  array
     */
    public function getModelTypes()
    {
        return $this->mf->getAllTypeNames();
    }

    /**
     * Finds all records (or filtered by specific identifiers) for a type.
     *
     * @todo    Add sorting and pagination (limit/skip).
     * @todo    Handle find all with identifiers.
     * @param   string  $typeKey        The model type.
     * @param   array   $idenitifiers   The model identifiers (optional).
     * @param   array   $fields
     * @param   array   $sort
     * @param   int     $offset
     * @param   int     $limit
     * @return  Collections\Collection
     */
    public function findAll($typeKey, array $identifiers = [], array $fields = [], array $sort = [], $offset = 0, $limit = 0)
    {
        $metadata = $this->getMetadataForType($typeKey);
        if (!empty($identifiers)) {
            throw StoreException::nyi('Finding multiple records with specified identifiers is not yet supported.');
        }
        $recordSet = $this->retrieveRecords($typeKey, $identifiers, $fields, $sort, $offset, $limit);
        $models = $this->loadModels($typeKey, $recordSet);
        return new Collections\Collection($metadata, $this, $models, $recordSet->totalCount());
    }

    /**
     * Queries records based on a provided set of criteria.
     *
     * @param   string      $typeKey    The model type.
     * @param   array       $criteria   The query criteria.
     * @param   array       $fields     Fields to include/exclude.
     * @param   array       $sort       The sort criteria.
     * @param   int         $offset     The starting offset, aka the number of Models to skip.
     * @param   int         $limit      The number of Models to limit.
     * @return  Collections\Collection
     */
    public function findQuery($typeKey, array $criteria, array $fields = [], array $sort = [], $offset = 0, $limit = 0)
    {
        $metadata = $this->getMetadataForType($typeKey);
        $persister = $this->getPersisterFor($typeKey);
        $this->dispatcher->dispatch(Events::preQuery, new PreQueryArguments($metadata, $this, $persister, $criteria));

        $recordSet = $persister->query($metadata, $this, $criteria, $fields, $sort, $offset, $limit);

        $models = $this->loadModels($typeKey, $recordSet);
        return new Collections\Collection($metadata, $this, $models, $recordSet->totalCount());
    }

    /**
     * Gets the model memory cache (identity map).
     *
     * @return  Cache
     */
    public function getModelCache()
    {
        return $this->cache;
    }

    /**
     * Searches for records (via the search layer) for a specific type, attribute, and value.
     * Uses the autocomplete logic to fullfill the request.
     *
     * @todo    Determine if full models should be return, or only specific fields.
     *          Autocompleters needs to be fast. If only specific fields are returned, do we need to exclude nulls in serialization?
     * @todo    Is search enabled for all models, by default, where everything is stored?
     *
     * @param   string  $typeKey
     * @param   string  $attributeKey
     * @param   string  $searchValue
     * @return  Collections\Collection
     */
    public function searchAutocomplete($typeKey, $attributeKey, $searchValue)
    {
        $metadata = $this->getMetadataForType($typeKey);
        if (false === $metadata->isSearchEnabled()) {
            throw StoreException::badRequest(sprintf('Search is not enabled for model type "%s"', $metadata->type));
        }
        return new Collections\Collection($metadata, $this, [], 0);
    }

    /**
     * Creates a new record.
     * The model will not be comitted to the persistence layer until $model->save() is called.
     *
     * @api
     * @param   string      $typeKey    The model type.
     * @param   string|null $identifier The model identifier. Generally should be null unless client-side id generation is in place.
     * @return  Model
     */
    public function create($typeKey, $identifier = null)
    {
        if (empty($identifier)) {
            $identifier = $this->generateIdentifier($typeKey);
        }
        return $this->createModel($typeKey, $identifier);
    }

    /**
     * Deletes a model.
     * The moel will be immediately deleted once retrieved.
     *
     * @api
     * @param   string      $typeKey    The model type.
     * @param   string|null $identifier The model identifier.
     * @return  Model
     */
    public function delete($typeKey, $identifier)
    {
        $model = $this->find($typeKey, $identifier);
        return $model->delete()->save();
    }

    /**
     * Retrieves a RecordSet object from the persistence layer.
     *
     * @param   string  $typeKey    The model type.
     * @param   string  $identifier The model identifier.
     * @return  array
     * @throws  StoreException  If the record cannot be found.
     */
    public function retrieveRecord($typeKey, $identifier)
    {
        $persister = $this->getPersisterFor($typeKey);
        $record = $persister->retrieve($this->getMetadataForType($typeKey), $identifier, $this)->getSingleResult();
        if (null === $record) {
            throw StoreException::recordNotFound($typeKey, $identifier);
        }
        return $record;
    }

    /**
     * Retrieves multiple Record objects from the persistence layer.
     *
     * @todo    Implement sorting and pagination (limit/skip).
     * @param   string  $typeKey        The model type.
     * @param   array   $identifiers    The model identifier.
     * @param   array   $fields
     * @param   array   $sort
     * @param   int     $offset
     * @param   int     $limit
     * @return  RecordSetInterface
     */
    public function retrieveRecords($typeKey, array $identifiers, array $fields = [], array $sort = [], $offset = 0, $limit = 0)
    {
        $persister = $this->getPersisterFor($typeKey);
        return $persister->all($this->getMetadataForType($typeKey), $this, $identifiers, $fields, $sort, $offset, $limit);
    }

    /**
     * Retrieves multiple Record objects from the persistence layer for an inverse relationship.
     *
     * @todo    Need to find a way to query all inverse at the same time for a findAll query, as it's queried multiple times.
     * @param   string  $ownerTypeKey
     * @param   string  $relTypeKey
     * @param   array   $identifiers
     * @param   string  $inverseField
     * @return  RecordSetInterface
     */
    public function retrieveInverseRecords($ownerTypeKey, $relTypeKey, array $identifiers, $inverseField)
    {
        $persister = $this->getPersisterFor($relTypeKey);
        return $persister->inverse(
            $this->getMetadataForType($ownerTypeKey),
            $this->getMetadataForType($relTypeKey),
            $this,
            $identifiers,
            $inverseField
        );
    }

    /**
     * Loads/creates a model from a persistence layer Record.
     *
     * @param   string  $typeKey    The model type.
     * @param   array   $record     The persistence layer record.
     * @return  Model
     */
    protected function loadModel($typeKey, array $record)
    {
        $this->mf->validateResourceTypes($typeKey, $record['type']);
        // Must use the type from the record to cover polymorphic models.
        $metadata = $this->getMetadataForType($record['type']);

        $model = new Model($metadata, $record['identifier'], $this, $record['properties']);
        $model->getState()->setLoaded();

        $this->dispatchLifecycleEvent(Events::postLoad, $model);

        $this->cache->push($model);
        return $model;
    }

    /**
     * Loads/creates multiple models from persistence layer Records.
     *
     * @param   string              $typeKey    The model type.
     * @param   RecordSetInterface  $records    The persistence layer records.
     * @return  Model[]
     */
    protected function loadModels($typeKey, RecordSetInterface $records)
    {
        $models = [];
        foreach ($records as $record) {
            $models[] = $this->loadModel($typeKey, $record);
        }
        return $models;
    }

    /**
     * Dispatches a model lifecycle event via the event dispatcher.
     *
     * @param   string  $eventName
     * @param   Model   $model
     * @return  self
     */
    protected function dispatchLifecycleEvent($eventName, Model $model)
    {
        $args = new ModelLifecycleArguments($model);
        $this->dispatcher->dispatch($eventName, $args);
        return $this;
    }

    /**
     * Creates a new Model instance.
     * Will not be persisted until $model->save() is called.
     *
     * @param   string  $typeKey    The model type.
     * @param   string  $identifier The model identifier.
     * @return  Model
     */
    protected function createModel($typeKey, $identifier)
    {
        if (true === $this->cache->has($typeKey, $identifier)) {
            throw new \RuntimeException(sprintf('A model is already loaded for type "%s" using identifier "%s"', $typeKey, $identifier));
        }
        $metadata = $this->getMetadataForType($typeKey);
        if (true === $metadata->isAbstract()) {
            throw StoreException::badRequest('Abstract models cannot be created directly. You must instantiate a child class');
        }
        $model = new Model($metadata, $identifier, $this);
        $model->getState()->setNew();
        $this->cache->push($model);
        return $model;
    }

    /**
     * Loads a has-one model proxy.
     *
     * @param   string  $relatedTypeKey
     * @param   string  $identifier
     * @return  Model
     */
    public function loadProxyModel($relatedTypeKey, $identifier)
    {
        $identifier = $this->convertId($identifier);
        if (true === $this->cache->has($relatedTypeKey, $identifier)) {
            return $this->cache->get($relatedTypeKey, $identifier);
        }

        $metadata = $this->getMetadataForType($relatedTypeKey);
        $model = new Model($metadata, $identifier, $this);
        $this->cache->push($model);
        return $model;
    }

    /**
     * Loads an Embed model
     *
     * @param   EmbedMetadata   $embedMeta
     * @param   array           $embed
     * @return  Embed
     */
    public function loadEmbed(EmbedMetadata $embedMeta, array $embed)
    {
        return new Embed($embedMeta, $this, $embed);
    }

    /**
     * Loads a has-many inverse model collection.
     *
     * @param   RelationshipMetadata    $relMeta
     * @param   Model                   $owner
     * @return  Collections\InverseCollection
     */
    public function createInverseCollection(RelationshipMetadata $relMeta, Model $owner)
    {
        $metadata = $this->getMetadataForType($relMeta->getEntityType());
        return new Collections\InverseCollection($metadata, $this, $owner, $relMeta->inverseField);
    }

    /**
     * Loads a has-many embed collection.
     *
     * @param   EmbeddedPropMetadata    $relMeta
     * @param   array|null              $embedDocs
     * @return  Collections\EmbedCollection
     */
    public function createEmbedCollection(EmbeddedPropMetadata $embededPropMeta, array $embedDocs = null)
    {
        if (empty($embedDocs)) {
            $embedDocs = [];
        }
        if (false === $this->isSequentialArray($embedDocs)) {
            throw StoreException::badRequest(sprintf('Improper has-many data detected for embed "%s" - a sequential array is required.', $embededPropMeta->getKey()));
        }

        $embeds = [];
        foreach ($embedDocs as $embedDoc) {
            $embeds[] = $this->loadEmbed($embededPropMeta->embedMeta, $embedDoc);
        }

        return new Collections\EmbedCollection($embededPropMeta->embedMeta, $this, $embeds);
    }

    /**
     * Loads a has-many model collection.
     *
     * @param   RelationshipMetadata    $relMeta
     * @param   array|null              $references
     * @return  Collections\Collection
     */
    public function createCollection(RelationshipMetadata $relMeta, array $references = null)
    {
        $metadata = $this->getMetadataForType($relMeta->getEntityType());
        if (empty($references)) {
            $references = [];
        }
        if (false === $this->isSequentialArray($references)) {
            throw StoreException::badRequest(sprintf('Improper has-many data detected for relationship "%s" - a sequential array is required.', $relMeta->getKey()));
        }
        $models = [];
        foreach ($references as $reference) {
            $models[] = $this->loadProxyModel($reference['type'], $reference['id']);
        }
        return new Collections\Collection($metadata, $this, $models, count($models));
    }

    /**
     * Determines the persister to use for the provided model key.
     *
     * @param   string  $typeKey    The model type key.
     * @return  PersisterInterface
     */
    public function getPersisterFor($typeKey)
    {
        $metadata = $this->getMetadataForType($typeKey);
        return $this->storageManager->getPersister($metadata->persistence->getKey());
    }

    /**
     * Loads/fills a collection of empty (unloaded) models with data from the persistence layer.
     *
     * @param   Collections\AbstractCollection  $collection
     * @return  Model[]
     */
    public function loadCollection(Collections\AbstractCollection $collection)
    {
        $identifiers = $collection->getIdentifiers();
        if (empty($identifiers)) {
            // Nothing to query.
            return [];
        }
        if ($collection instanceof Collections\InverseCollection) {
            $recordSet = $this->retrieveInverseRecords($collection->getOwner()->getType(), $collection->getType(), $collection->getIdentifiers(), $collection->getQueryField());
        } else {
            $recordSet = $this->retrieveRecords($collection->getType(), $collection->getIdentifiers());
        }

        $models = [];
        foreach ($recordSet as $record) {
            if (true === $this->cache->has($record['type'], $record['identifier'])) {
                $models[] = $this->cache->get($record['type'], $record['identifier']);
                continue;
            }
            $models[] = $this->loadModel($collection->getType(), $record);
        }
        return $models;
    }

    /**
     * Commits a model by persisting it to the database.
     *
     * @todo    Eventually we'll want to schedule models and allow for mutiple commits, flushes, etc.
     * @todo    Will need to handle cascade saving of new or modified relationships??
     * @param   Model   $model  The model to commit.
     * @return  Model
     */
    public function commit(Model $model)
    {
        $this->dispatchLifecycleEvent(Events::preCommit, $model);

        if (false === $this->shouldCommit($model)) {
            return $model;
        }

        if (true === $model->getState()->is('new')) {
            $this->doCommitCreate($model);

        } elseif (true === $model->getState()->is('deleting')) {
            // Deletes must execute before updates to prevent an update then a delete.
            $this->doCommitDelete($model);

        } elseif (true === $model->isDirty()) {
            $this->doCommitUpdate($model);

        } else {
            throw new \RuntimeException('Unable to commit model.');
        }

        $this->dispatchLifecycleEvent(Events::postCommit, $model);

        return $model;
    }

    /**
     * Performs a Model creation commit and persists to the database.
     *
     * @param   Model   $model
     * @return  Model
     */
    private function doCommitCreate(Model $model)
    {
        $this->dispatchLifecycleEvent(Events::preCreate, $model);

        $this->getPersisterFor($model->getType())->create($model);
        $model->getState()->setNew(false);
        // Should the model always reload? Or should the commit be assumed correct and just clear the new/dirty state?
        $model->reload();

        $this->dispatchLifecycleEvent(Events::postCreate, $model);
        return $model;
    }

    /**
     * Performs a Model delete commit and persists to the database.
     *
     * @param   Model   $model
     * @return  Model
     */
    private function doCommitDelete(Model $model)
    {
        $this->dispatchLifecycleEvent(Events::preDelete, $model);

        $this->getPersisterFor($model->getType())->delete($model);
        $model->getState()->setDeleted();

        $this->dispatchLifecycleEvent(Events::postDelete, $model);
        return $model;
    }

    /**
     * Performs a Model update commit and persists to the database.
     *
     * @param   Model   $model
     * @return  Model
     */
    private function doCommitUpdate(Model $model)
    {
        $this->dispatchLifecycleEvent(Events::preUpdate, $model);

        $this->getPersisterFor($model->getType())->update($model);
        // Should the model always reload? Or should the commit be assumed correct and just clear the new/dirty state?
        $model->reload();

        $this->dispatchLifecycleEvent(Events::postUpdate, $model);
        return $model;
    }

    /**
     * Validates that an embed name/type can be set to an owning embed metadata type.
     *
     * @param   EmbedMetadata   $owningMeta     The metadata the type will be added to.
     * @param   string          $nameToCheck    The name to check.
     * @return  self
     * @throws  StoreException  If the type to add is not supported.
     */
    public function validateEmbedSet(EmbedMetadata $owningMeta, $nameToCheck)
    {
        if ($owningMeta->name !== $nameToCheck) {
            throw StoreException::badRequest(sprintf('The embed type "%s" cannot be added to "%s", as it is not supported.', $nameToCheck, $owningMeta->name));
        }
        return $this;
    }

    /**
     * Validates that a model type can be set to an owning metadata type.
     *
     * @param   EntityMetadata  $owningMeta The metadata the type will be added to.
     * @param   string          $typeToAdd  The type to add.
     * @return  self
     * @throws  StoreException  If the type to add is not supported.
     */
    public function validateRelationshipSet(EntityMetadata $owningMeta, $typeToAdd)
    {
        if (true === $owningMeta->isPolymorphic()) {
            $canSet = in_array($typeToAdd, $owningMeta->ownedTypes);
        } else {
            $canSet = $owningMeta->type === $typeToAdd;
        }
        if (false === $canSet) {
            throw StoreException::badRequest(sprintf('The model type "%s" cannot be added to "%s", as it is not supported.', $typeToAdd, $owningMeta->type));
        }
        return $this;
    }

    /**
     * Converts an attribute value to the proper Modlr data type.
     *
     * @param   string  $dataType   The data type, such as string, integer, boolean, etc.
     * @param   mixed   $value      The value to convert.
     * @return  mixed
     */
    public function convertAttributeValue($dataType, $value)
    {
        return $this->typeFactory->convertToModlrValue($dataType, $value);
    }

    /**
     * Determines if a model is eligible for commit.
     *
     * @todo    Does delete need to be here?
     * @param   Model   $model
     * @return  bool
     */
    protected function shouldCommit(Model $model)
    {
        $state = $model->getState();
        return $model->isDirty() || $state->is('new') || $state->is('deleting');
    }

    /**
     * Generates a new identifier value for a model type.
     *
     * @param   string  $typeKey    The model type.
     * @return  string
     */
    protected function generateIdentifier($typeKey)
    {
        return $this->convertId($this->getPersisterFor($typeKey)->generateId());
    }

    /**
     * Converts the id value to a normalized string.
     *
     * @param   mixed   $identenfier    The identifier to convert.
     * @return  string
     */
    protected function convertId($identifier)
    {
        return (String) $identifier;
    }

    /**
     * Gets the metadata for a model type.
     *
     * @param   string  $typeKey    The model type.
     * @return  EntityMetadata
     */
    public function getMetadataForType($typeKey)
    {
        return $this->mf->getMetadataForType($typeKey);
    }

    /**
     * Gets the metadata for a relationship.
     *
     * @param   RelationshipMetadata    $relMeta    The relationship metadata.
     * @return  EntityMetadata
     */
    public function getMetadataForRelationship(RelationshipMetadata $relMeta)
    {
        return $this->getMetadataForType($relMeta->getEntityType());
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
