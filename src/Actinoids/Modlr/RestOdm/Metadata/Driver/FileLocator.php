<?php

namespace As3\Modlr\RestOdm\Metadata\Driver;

/**
 * File locator service for locating metadata files for use in creating EntityMetadata instances.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
final class FileLocator implements FileLocatorInterface
{
    /**
     * Directories to search in.
     *
     * @var array
     */
    private $directories = [
        'model'     => [],
        'mixin'     => [],
    ];

    /**
     * Constructor.
     *
     * @param   string|array   $modelDirs
     * @param   string|array   $mixinDirs
     */
    public function __construct($modelDirs, $mixinDirs = [])
    {
        $this->directories['model'] = (Array) $modelDirs;
        $this->directories['mixin'] = (Array) $mixinDirs;
    }

    /**
     * Gets the directories to search in.
     *
     * @param   string  $type   The directory type, either model or mixin.
     * @return  array
     */
    protected function getDirectories($type)
    {
        return $this->directories[$type];
    }

    /**
     * {@inheritDoc}
     */
    public function findFileForMixin($mixinName, $extension)
    {
        return $this->findFile('mixin', $mixinName, $extension);
    }

    /**
     * {@inheritDoc}
     */
    public function findFileForType($modelType, $extension)
    {
        return $this->findFile('model', $modelType, $extension);
    }

    /**
     * Finds a file based on a directory type (model or mixin) and a key.
     *
     * @param   string  $dirType
     * @param   string  $key
     * @param   string  $extension
     * @return  string|null
     */
    protected function findFile($dirType, $key, $extension)
    {
        foreach ($this->getDirectories($dirType) as $dir) {
            $path = sprintf('%s/%s', $dir, $this->getFilename($key, $extension));
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

        foreach ($this->getDirectories('model') as $dir) {
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
     * Gets the filename for a metadata entity or mixin.
     *
     * @param   string      $key
     * @param   string      $extension
     * @return  string
     */
    protected function getFilename($key, $extension)
    {
        return sprintf('%s.%s', $key, $extension);
    }
}
