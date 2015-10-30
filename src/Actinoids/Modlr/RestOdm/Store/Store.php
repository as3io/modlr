<?php

namespace Actinoids\Modlr\RestOdm\Store;

use Actinoids\Modlr\RestOdm\Models\Model;
use Actinoids\Modlr\RestOdm\Metadata\MetadataFactory;
use Actinoids\Modlr\RestOdm\Persister\PersisterInterface;
use Actinoids\Modlr\RestOdm\Persister\Record;

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
     * The persister for retrieve and saving records to the database layer.
     *
     * @todo Eventually this should be replaced by a persister manager and not injected directly.
     * @var  PersisterInterface
     */
    private $persister;

    /**
     * The identity map.
     * Contains all models currently loaded in memory.
     *
     * @var array
     */
    private $identityMap = [];

    /**
     * Constructor.
     *
     * @param   MetadataFactory     $mf
     * @param   PersisterInterface  $persister
     */
    public function __construct(MetadataFactory $mf, PersisterInterface $persister)
    {
        $this->mf = $mf;
        $this->persister = $persister;
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
        if (true === $this->inIdentityMap($typeKey, $identifier)) {
            return $this->getFromIdentityMap($typeKey, $identifier);
        }
        $record = $this->retrieveRecord($typeKey, $identifier);
        return $this->loadModel($typeKey, $record);
    }

    /**
     * Creates a new record.
     * The model will not be comitted to the persistence layer until $model->save() is called.
     *
     * @api
     * @param   string      $typeKey    The model type.
     * @param   string|null $identifier The model identifier. Generally should be null unless client-side id generation is in place.
     * @param   array       $properties Any key/value properties to initalize the new record with.
     * @return  Model
     */
    public function create($typeKey, $identifier = null, array $properties = [])
    {
        if (empty($identifier)) {
            $identifier = $this->generateIdentifier($typeKey);
        }
        return $this->createModel($typeKey, $identifier, $properties);
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
        $record = $persister->retrieve($this->getMetadataForType($typeKey), $identifier);
        if (null === $record) {
            throw StoreException::recordNotFound($typeKey, $identifier);
        }
        return $record;
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

        $model = new Model($metadata, $record->getId(), $this, $record->getProperties());
        $model->getState()->setLoaded();
        $this->pushIdentityMap($model);
        return $model;
    }

    /**
     * Creates a new Model instance.
     * Will not be persisted until $model->save() is called.
     *
     * @param   string  $typeKey    The model type.
     * @param   string  $identifier The model identifier.
     * @param   array   $properties Any key/value properties to initalize the new record with.
     */
    protected function createModel($typeKey, $identifier, array $properties = [])
    {
        if (true === $this->inIdentityMap($typeKey, $identifier)) {
            throw new \RuntimeException(sprintf('A model is already loaded for type "%s" using identifier "%s"', $typeKey, $identifier));
        }
        $metadata = $this->getMetadataForType($typeKey);
        if (true === $metadata->isAbstract()) {
            throw StoreException::badRequest('Abstract models cannot be created directly. You must instantiate a child class');
        }
        $model = new Model($metadata, $identifier, $this, $properties);
        $model->getState()->setNew();
        $this->pushIdentityMap($model);
        return $model;
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
        return (String) $this->getPersisterFor($typeKey)->generateId();
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


    protected function getIdentityMapForType($typeKey)
    {
        if (isset($this->identityMap[$typeKey])) {
            return $this->identityMap[$typeKey];
        }
        return [];
    }

    protected function removeFromIdentityMap($typeKey, $identifier)
    {
        if (isset($this->identityMap[$typeKey][$identifier])) {
            unset($this->identityMap[$typeKey][$identifier]);
        }
        return $this;
    }

    protected function getFromIdentityMap($typeKey, $identifier)
    {
        $map = $this->getIdentityMapForType($typeKey);
        if (isset($map[$identifier])) {
            return $map[$identifier];
        }
        return null;
    }

    protected function pushIdentityMap(Model $model)
    {
        $this->identityMap[$model->getType()][$model->getId()] = $model;
        return $this;
    }

    public function inIdentityMap($typeKey, $identifier)
    {
        return null !== $this->getFromIdentityMap($typeKey, $identifier);
    }
}
