<?php

namespace Actinoids\Modlr\RestOdm\Metadata\Driver;

/**
 * File locator service for locating metadata files for use in creating EntityMetadata instances.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class FileLocator implements FileLocatorInterface
{
    /**
     * Directories to search in.
     *
     * @var array
     */
    private $directories = [];

    /**
     * Constructor.
     *
     * @param   string|array   $directories
     */
    public function __construct($directories)
    {
        $this->directories = (Array) $directories;
    }

    /**
     * Gets the directories to search in.
     *
     * @return  array
     */
    public function getDirectories()
    {
        return $this->directories;
    }

    /**
     * {@inheritDoc}
     */
    public function findFileForType($type, $extension)
    {
        foreach ($this->getDirectories() as $dir) {
            $path = sprintf('%s/%s', $dir, $this->getFilenameForType($type, $extension));
            if (file_exists($path)) {
                return $path;
            }
        }
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function findAllTypes($extension)
    {
        $types = [];
        $extension = sprintf('.%s', $extension);

        foreach ($this->getDirectories() as $dir) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                if (($fileName = $file->getBasename($extension)) == $file->getBasename()) {
                    continue;
                }
                $types[] = str_replace($extension, '', $fileName);
            }
        }
        return $types;
    }

    /**
     * Gets the filename for a metadata entity type.
     *
     * @param   string      $type
     * @param   string      $extension
     * @return  string
     */
    public function getFilenameForType($type, $extension)
    {
        return sprintf('%s.%s', $type, $extension);
    }
}
