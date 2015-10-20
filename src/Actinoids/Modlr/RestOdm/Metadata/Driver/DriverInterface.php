<?php

namespace Actinoids\Modlr\RestOdm\Metadata\Driver;

/**
 * Interface for metadata driver implementations.
 * Drivers generally should not be responsible for validating the created metadata objects, nor generally should not throw Exceptions. (Unless there are fatal errors).
 * Instead they should be concerned with loading a complete (defaulted if necessary) metadata object.
 * The MetadataFactory is then responsible for validating the objects created by the driver.
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
