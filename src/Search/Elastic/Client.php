<?php

namespace As3\Modlr\Search\Elastic;

use As3\Modlr\Metadata\EntityMetadata;
use As3\Modlr\Metadata\Interfaces\SearchMetadataFactoryInterface;
use As3\Modlr\Persister\PersisterInterface;
use As3\Modlr\Persister\Record;
use As3\Modlr\Search\ClientInterface;

/**
 * Client for searching (and modifying) models via Elastic search.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
final class Client implements ClientInterface
{
    const CLIENT_KEY = 'elastic';

    /**
     * @var StorageMetadataFactory
     */
    private $smf;

    /**
     * Constructor.
     *
     * @param   StorageMetadataFactory  $smf
     */
    public function __construct(StorageMetadataFactory $smf)
    {
        $this->smf = $smf;
    }

    /**
     * {@inheritDoc}
     */
    public function autocomplete(EntityMetadata $metadata, $attributeKey, $searchValue)
    {
        var_dump(__METHOD__);
        die();
    }

    /**
     * {@inheritDoc}
     */
    public function getClientKey()
    {
        return self::CLIENT_KEY;
    }

    /**
     * {@inheritDoc}
     */
    public function getSearchMetadataFactory()
    {
        return $this->smf;
    }

    /**
     * {@inheritDoc}
     */
    public function query(EntityMetadata $metadata, array $criteria, PersisterInterface $persister)
    {
        return [];
    }
}
