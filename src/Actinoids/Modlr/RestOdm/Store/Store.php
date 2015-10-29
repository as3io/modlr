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
    private $mf;

    private $persister;

    private $identityMap = [];

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
     * @param   string      $typeKey    The model type.
     * @param   string      $identifier The model id.
     * @param   bool        $reload     Whether to force a reload.
     * @return  Model
     * @throws  StoreException If the record cannot be found from the persistence layer.
     */
    public function findRecord($typeKey, $identifier, $reload = false)
    {
        if (true === $this->inIdentityMap($typeKey, $identifier) && false === $reload) {
            return $this->getFromIdentityMap($typeKey, $identifier);
        }

        $persister = $this->getPersisterFor($typeKey);
        $record = $persister->retrieve($this->getMetadataForType($typeKey), $identifier);
        if (null === $record) {
            throw StoreException::recordNotFound($type, $identifier);
        }
        return $this->load($typeKey, $record);
    }

    public function createRecord($typeKey, $identifier = null, array $data = [])
    {
        if (empty($identifier)) {
            $identifier = $this->generateIdentifier($typeKey);
        }
        return $this->createModel($typeKey, $identifier, $data);
    }

    protected function load($typeKey, Record $record)
    {
        $this->mf->validateResourceTypes($typeKey, $record->getType());
        if (true === $this->inIdentityMap($record->getType(), $record->getId())) {
            // Safety in case findRecord is called multiple times for the same record while it's already loaded.
            return $this->getFromIdentityMap($record->getType(), $record->getId());
        }
        // Must use the type from the record to cover polymorphic models.
        $metadata = $this->getMetadataForType($record->getType());
        $model = new Model($metadata, $record->getId(), $this, $record->getProperties());
        $model->getState()->setLoaded();
        $this->pushIdentityMap($model);
        return $model;
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

    protected function generateIdentifier($typeKey)
    {
        return $this->getPersisterFor($typeKey)->generateId();
    }



    public function getMetadataForType($typeKey)
    {
        return $this->mf->getMetadataForType($typeKey);
    }



    protected function createModel($typeKey, $identifier, array $data = [])
    {
        if (true === $this->inIdentityMap($typeKey, $identifier)) {
            throw new \RuntimeException(sprintf('A model is already loaded for type "%s" using identifier "%s"', $typeKey, $identifier));
        }
        $metadata = $this->getMetadataForType($typeKey);
        if (true === $metadata->isAbstract()) {
            throw StoreException::badRequest('Abstract models cannot be created directly. You must instantiate a child class');
        }
        $model = new Model($metadata, $identifier, $this, $data);
        $model->getState()->setNew();
        $this->pushIdentityMap($model);
        return $model;
    }

    protected function getIdentityMapForType($typeKey)
    {
        if (isset($this->identityMap[$typeKey])) {
            return $this->identityMap[$typeKey];
        }
        return [];
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
