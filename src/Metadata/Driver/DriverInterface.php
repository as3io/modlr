<?php

namespace As3\Modlr\Metadata\Driver;

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
     * Loads the MixinMetadata for a mixin definition.
     *
     * @param   string  $embedName
     * @return  \As3\Modlr\Metadata\EmbedMetadata|null
     */
    public function loadMetadataForEmbed($embedName);

    /**
     * Loads the EntityMetadata for a type.
     *
     * @param   string  $type
     * @return  \As3\Modlr\Metadata\EntityMetadata|null
     */
    public function loadMetadataForType($type);

    /**
     * Loads the MixinMetadata for a mixin definition.
     *
     * @param   string  $mixinName
     * @return  \As3\Modlr\Metadata\MixinMetadata|null
     */
    public function loadMetadataForMixin($mixinName);

    /**
     * Gets all type names.
     *
     * @return  array
     */
    public function getAllTypeNames();

    /**
     * Gets the persistence metadata factory service, based on a persister key.
     *
     * @param   string  $persisterKey
     * @return  \As3\Modlr\Metadata\Interfaces\PersistenceMetadataFactoryInterface
     */
    public function getPersistenceMetadataFactory($persisterKey);

    /**
     * Gets the type hierarchy (via extension) for an entity type.
     * Is recursive.
     *
     * @param   string  $type
     * @param   array   $types
     * @return  array
     */
    public function getTypeHierarchy($type, array $types = []);

    /**
     * Gets all types owned by the provided type.
     * Is recursive.
     *
     * @param   string  $type
     * @param   array   $types
     * @return  array
     */
    public function getOwnedTypes($type, array $types = []);
}
