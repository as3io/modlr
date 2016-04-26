<?php

namespace As3\Modlr\Search;

use As3\Modlr\Metadata\EntityMetadata;
use As3\Modlr\Metadata\Interfaces\SearchMetadataFactoryInterface;
use As3\Modlr\Persister\PersisterInterface;
use As3\Modlr\Persister\RecordSetInterface;

/**
 * Defines the service implementation for searching for (and modifying) models in the search layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface ClientInterface
{
    /**
     * Gets the key name for this search client.
     * Is used to uniquely indentify this search type by the search manager.
     * Is also the type key for the search metadata layer.
     *
     * @return  string
     */
    public function getClientKey();

    /**
     * Gets the search metadata factory for creating SearchInterface instances.
     *
     * @return  SearchMetadataFactoryInterface
     */
    public function getSearchMetadataFactory();

    /**
     * Queries/searchs for records for the specified type.
     * Uses the search query, runs the search, and then loads the full models from the persistence layer.
     *
     * @todo    Implement sorting and pagination (limit/skip).
     * @param   EntityMetadata      $metadata
     * @param   array               $criteria
     * @param   PersisterInterface  $persister
     * @return  RecordSetInterface
     */
    public function query(EntityMetadata $metadata, array $criteria, PersisterInterface $persister);

    /**
     * Returns a set of autocomplete results for a model type, attribute key, and search value.
     *
     * @param   string  $typeKey
     * @param   string  $attributeKey
     * @param   string  $searchValue
     * @return  AutocompleteResult[]
     */
    public function autocomplete(EntityMetadata $metadata, $attributeKey, $searchValue);
}
