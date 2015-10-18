<?php

namespace Actinoids\Modlr\RestOdm\Metadata\Driver;

/**
 * Interface for metadata driver implementations.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface DriverInterface
{
    /**
     * Loads the EntityMetadata for a type.
     *
     * @param   string  $type
     * @return  \Zarathustra\Modlr\RestOdm\Metadata\EntityMetadata|null
     */
    public function loadMetadataForType($type);

    /**
     * Gets all type names.
     *
     * @return  array
     */
    public function getAllTypeNames();

    /**
     * Gets the type hierarchy (via extension) for an entity type.
     * Is recursive.
     *
     * @param   string  $type
     * @param   array   $types
     * @return  array
     */
    public function getTypeHierarchy($type, array $types = []);
}
