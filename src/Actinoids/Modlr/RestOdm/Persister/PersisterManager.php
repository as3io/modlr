<?php

namespace Actinoids\Modlr\RestOdm\Persister;

use Actinoids\Modlr\RestOdm\Store\Store;
use Actinoids\Modlr\RestOdm\Models\Model;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;

/**
 * Registers all available Persister services by key.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class PersisterManager
{
    /**
     * All registered Persister services.
     *
     * @var PersisterInterface[]
     */
    private $persisters = [];

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
     * Gets a Persister service by key.
     *
     * @param   string  $key
     * @return  PersisterInterface
     * @throws  PersisterException  If the service was not found.
     */
    public function getPersister($key)
    {
        if (false === $this->hasPersister($key)) {
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
}
