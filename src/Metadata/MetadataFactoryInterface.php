<?php

namespace As3\Modlr\Metadata;

/**
 * Defines the implementation of a MetadataFactory object.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface MetadataFactoryInterface
{
    /**
     * Returns EntityMetadata for the given entity type.
     *
     * @param   string              $type
     * @return  EntityMetadata
     * @throws  \Zarathustra\Modlr\RestOdm\Exception\MetadataException  If metadata was not found.
     */
    public function getMetadataForType($type);

    /**
     * Gets all EntityMetadata for known entities, keyed by entity type.
     *
     * @return  EntityMetadata[]
     */
    public function getAllMetadata();

    /**
     * Determines if EntityMetadata exists for the given entity type.
     *
     * @return  bool
     */
    public function metadataExists($type);

    /**
     * Gets all available Entity type names.
     *
     * @return  array
     */
    public function getAllTypeNames();
}
