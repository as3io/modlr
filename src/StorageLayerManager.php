<?php

namespace As3\Modlr;

use As3\Modlr\Persister\PersisterException;
use As3\Modlr\Persister\PersisterInterface;
use As3\Modlr\Search\ClientException;
use As3\Modlr\Search\ClientInterface;

/**
 * Registers all available storage layers services (search and persistence) by key.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class StorageLayerManager
{
    /**
     * All registered Persister services.
     *
     * @var PersisterInterface[]
     */
    private $persisters = [];

    /**
     * All Search Client services.
     *
     * @var ClientInterface[]
     */
    private $searchClients = [];

    /**
     * Adds/registers a Persister service to the manager.
     *
     * @param   PersisterInterface  $persister
     * @return  self
     */
    public function addPersister(PersisterInterface $persister)
    {
        $this->persisters[$persister->getPersisterKey()] = $persister;
        return $this;
    }

    /**
     * Adds/registers a Search Client service to the manager.
     *
     * @param   ClientInterface     $client
     * @return  self
     */
    public function addSearchClient(ClientInterface $client)
    {
        $this->searchClients[$client->getClientKey()] = $client;
        return $this;
    }

    /**
     * Gets a Persister service by key.
     *
     * @param   string  $key
     * @return  PersisterInterface
     * @throws  PersisterException  If the service was not found.
     */
    public function getPersister($key)
    {
        if (empty($key) || false === $this->hasPersister($key)) {
            throw PersisterException::persisterNotFound($key);
        }
        return $this->persisters[$key];
    }

    /**
     * Determines if a Persister exists.
     *
     * @param   string  $key
     * @return  bool
     */
    public function hasPersister($key)
    {
        return isset($this->persisters[$key]);
    }

    /**
     * Gets a Search Client service by key.
     *
     * @param   string  $key
     * @return  ClientInterface
     * @throws  ClientException  If the service was not found.
     */
    public function getSearchClient($key)
    {
        if (empty($key) || false === $this->hasSearchClient($key)) {
            throw ClientException::clientNotFound($key);
        }
        return $this->searchClients[$key];
    }

    /**
     * Determines if a Search Client exists.
     *
     * @param   string  $key
     * @return  bool
     */
    public function hasSearchClient($key)
    {
        return isset($this->searchClients[$key]);
    }
}
