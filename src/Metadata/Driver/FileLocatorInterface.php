<?php

namespace As3\Modlr\Metadata\Driver;

/**
 * Interface for locating metadata mapping files for generating EntityMetadata classes.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface FileLocatorInterface
{
    /**
     * Finds the file location for a metadata embed file.
     *
     * @param   string  $embedName
     * @param   string  $extension
     *
     * @return  string|null
     */
    public function findFileForEmbed($embedName, $extension);

    /**
     * Finds the file location for a metadata file (for loading an EntityMetadata class instance), based on entity type.
     *
     * @param   string  $type
     * @param   string  $extension
     *
     * @return  string|null
     */
    public function findFileForType($type, $extension);

    /**
     * Finds the file location for a metadata mixin file.
     *
     * @param   string  $mixinName
     * @param   string  $extension
     *
     * @return  string|null
     */
    public function findFileForMixin($mixinName, $extension);

    /**
     * Finds all possible metadata files.
     *
     * @param   string  $extension
     * @return  array
     */
    public function findAllTypes($extension);
}
