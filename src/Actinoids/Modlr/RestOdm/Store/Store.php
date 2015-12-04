<?php

namespace Actinoids\Modlr\RestOdm\Store;

use Actinoids\Modlr\RestOdm\Models\Model;
use Actinoids\Modlr\RestOdm\Models\Collection;
use Actinoids\Modlr\RestOdm\Models\AbstractCollection;
use Actinoids\Modlr\RestOdm\Models\InverseCollection;
use Actinoids\Modlr\RestOdm\Metadata\MetadataFactory;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Metadata\RelationshipMetadata;
use Actinoids\Modlr\RestOdm\Persister\PersisterInterface;
use Actinoids\Modlr\RestOdm\Persister\Record;
use Actinoids\Modlr\RestOdm\DataTypes\TypeFactory;
use Actinoids\Modlr\RestOdm\Events\EventDispatcher;
use Actinoids\Modlr\RestOdm\Store\Events\ModelLifecycleArguments;

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
     * The persister for retrieve and saving records to the database layer.
     *
     * @todo Eventually this should be replaced by a persister manager and not injected directly.
     * @var  PersisterInterface
     */
    private $persister;

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
     * @param   MetadataFactory     $mf
     * @param   PersisterInterface  $persister
     */
    public function __construct(MetadataFactory $mf, PersisterInterface $persister, TypeFactory $typeFactory, EventDispatcher $dispatcher)
    {
        $this->mf = $mf;
        $this->persister = $persister;
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
     * Finds all records (or filtered by specific identifiers) for a type.
     *
     * @todo    Add sorting and pagination (limit/skip).
     * @todo    Handle find all with identifiers.
     * @param   string  $typeKey        The model type.
     * @param   array   $idenitifiers   The model identifiers (optional).
     * @return  Collection
     */
    public function findAll($typeKey, array $identifiers = [])
    {
        $metadata = $this->getMetadataForType($typeKey);
        if (!empty($identifiers)) {
            throw StoreException::nyi('Finding multiple records with specified identifiers is not yet supported.');
        }
        $models = [];
        foreach ($this->retrieveRecords($typeKey, $identifiers) as $record) {
            $models[] = $this->loadModel($typeKey, $record);
        }
        $collection = new Collection($metadata, $this, $models);
        return $collection;
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
     * Retrieves a Record object from the persistence layer.
     *
     * @param   string  $typeKey    The model type.
     * @param   string  $identifier The model identifier.
     * @return  Record
     * @throws  StoreException  If the record cannot be found.
     */
    public function retrieveRecord($typeKey, $identifier)
    {
        $persister = $this->getPersisterFor($typeKey);
        $record = $persister->retrieve($this->getMetadataForType($typeKey), $identifier, $this);
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
     * @return  Record[]
     */
    public function retrieveRecords($typeKey, array $identifiers)
    {
        $persister = $this->getPersisterFor($typeKey);
        return $persister->all($this->getMetadataForType($typeKey), $this, $identifiers);
    }

    /**
     * Retrieves multiple Record objects from the persistence layer for an inverse relationship.
     *
     * @todo    Need to find a way to query all inverse at the same time for a findAll query, as it's queried multiple times.
     * @param   string  $ownerTypeKey
     * @param   string  $relTypeKey
     * @param   array   $identifiers
     * @param   string  $inverseField
     * @return  Record[]
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
     * @param   Record  $record     The persistence layer record.
     * @return  Model
     */
    protected function loadModel($typeKey, Record $record)
    {
        $this->mf->validateResourceTypes($typeKey, $record->getType());
        // Must use the type from the record to cover polymorphic models.
        $metadata = $this->getMetadataForType($record->getType());

        $model = new Model($metadata, $record->getId(), $this, $record);
        $model->getState()->setLoaded();

        $this->dispatchLifecycleEvent(Events::postLoad, $model);

        $this->cache->push($model);
        return $model;
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
     * Loads a has-many inverse model collection.
     *
     * @param   RelationshipMetadata    $relMeta
     * @param   Model                   $owner
     * @return  InverseCollection
     */
    public function createInverseCollection(RelationshipMetadata $relMeta, Model $owner)
    {
        $metadata = $this->getMetadataForType($relMeta->getEntityType());
        return new InverseCollection($metadata, $this, $owner, $relMeta->inverseField);
    }

    /**
     * Loads a has-many model collection.
     *
     * @param   RelationshipMetadata    $relMeta
     * @param   array|null              $references
     * @return  Collection
     */
    public function createCollection(RelationshipMetadata $relMeta, array $references = null)
    {
        $metadata = $this->getMetadataForType($relMeta->getEntityType());
        if (empty($references)) {
            $references = [];
        }
        if (false === $this->isSequentialArray($references)) {
            throw StoreException::badRequest(sprintf('Improper has-many data detected for relationship "%s" - a sequential array is required.', $relatedTypeKey));
        }
        $models = [];
        foreach ($references as $reference) {
            $models[] = $this->loadProxyModel($reference['type'], $reference['id']);
        }
        return new Collection($metadata, $this, $models);
    }

    /**
     * Loads/fills a collection of empty (unloaded) models with data from the persistence layer.
     *
     * @param   AbstractCollection  $collection
     * @return  Model[]
     */
    public function loadCollection(AbstractCollection $collection)
    {
        $identifiers = $collection->getIdentifiers();
        if (empty($identifiers)) {
            // Nothing to query.
            return $collection;
        }
        if ($collection instanceof InverseCollection) {
            $records = $this->retrieveInverseRecords($collection->getOwner()->getType(), $collection->getType(), $collection->getIdentifiers(), $collection->getQueryField());
        } else {
            $records = $this->retrieveRecords($collection->getType(), $collection->getIdentifiers());
        }

        $models = [];
        foreach ($records as $record) {
            if (true === $this->cache->has($record->getType(), $record->getId())) {
                $models[] = $this->cache->get($record->getType(), $record->getId());
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
        if (false === $this->shouldCommit($model)) {
            return $model;
        }
        $persister = $this->getPersisterFor($model->getType());
        if (true === $model->getState()->is('new')) {
            $persister->create($model);
            $model->getState()->setNew(false);
            // Should the model always reload? Or should the commit be assumed correct and just clear the new/dirty state?
            $model->reload();
        } elseif (true === $model->getState()->is('deleting')) {
            // Deletes must execute before updates to prevent an update then a delete.
            $persister->delete($model);
            $model->getState()->setDeleted();
        } elseif (true === $model->getState()->is('dirty')) {
            $persister->update($model);
            // Should the model always reload? Or should the commit be assumed correct and just clear the new/dirty state?
            $model->reload();
        } else {
            throw new \RuntimeException('Unable to commit model.');
        }
        return $model;
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
        return true === $state->is('dirty') || $state->is('new') || $state->is('deleting');
    }

    /**
     * Determines the persister to use for the provided model key.
     *
     * @todo    The persister should NOT be injected directly, but should have a persister manager service.
     * @todo    Instead, a persister should be chosen based on the metadata for the provided type.
     * @todo    Should throw an exception if persister metadata was unable to be found, or the service doesn't exist.
     * @param   string  $typeKey    The model type key.
     * @return  PersisterInterface
     */
    protected function getPersisterFor($typeKey)
    {
        return $this->persister;
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
