<?php

namespace Actinoids\Modlr\RestOdm\Search\Elastic;

use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;
use Actinoids\Modlr\RestOdm\Metadata\Interfaces\SearchMetadataFactoryInterface;
use Actinoids\Modlr\RestOdm\Persister\PersisterInterface;
use Actinoids\Modlr\RestOdm\Persister\Record;
use Actinoids\Modlr\RestOdm\Search\ClientInterface;

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
