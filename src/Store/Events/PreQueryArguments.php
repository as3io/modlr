<?php

namespace As3\Modlr\Store\Events;

use As3\Modlr\Events\EventArguments;
use As3\Modlr\Metadata\EntityMetadata;
use As3\Modlr\Persister\PersisterInterface;
use As3\Modlr\Store\Store;

/**
 * Pre-query event args.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class PreQueryArguments extends EventArguments
{
    /**
     * @var array
     */
    private $criteria;

    /**
     * @var EntityMetadata
     */
    private $metadata;

    /**
     * @var PersisterInterface
     */
    private $persister;

    /**
     * @var Store
     */
    private $store;

    /**
     * Constructor.
     *
     * @param   Store               $store
     * @param   PersisterInterface  $persister
     * @param   array               $criteria
     */
    public function __construct(EntityMetadata $metadata, Store $store, PersisterInterface $persister, array &$criteria)
    {
        $this->metadata = $metadata;
        $this->store = $store;
        $this->persister = $persister;
        $this->criteria = &$criteria;
    }

    /**
     * Gets the metadata.
     *
     * @return  EntityMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Gets the persister.
     *
     * @return  PersisterInterface
     */
    public function getPersister()
    {
        return $this->persister;
    }

    /**
     * Gets the store.
     *
     * @return  Store
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Gets the criteria (by reference) so it can be manipulated.
     *
     * @return  array
     */
    public function &getCriteria()
    {
        return $this->criteria;
    }
}
