<?php

namespace As3\Modlr\Metadata\Traits;

use As3\Modlr\Metadata\FieldMetadata;

/**
 * Common property metadata get methods.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
trait PropertiesTrait
{
    /**
     * Gets merged properties that this object contains.
     * Is a combination of attributes, relationships, and/or embeds, depending what the object supports.
     *
     * @return  FieldMetadata[]
     */
    abstract public function getProperties();

    /**
     * Determines whether search is enabled.
     *
     * @return  bool
     */
    public function isSearchEnabled()
    {
        $propertes = $this->getSearchProperties();
        return !empty($propertes);
    }

    /**
     * Gets all properties that are flagged for storage in search.
     *
     * @return  FieldMetadata[]
     */
    public function getSearchProperties()
    {
        static $props;
        if (null !== $props) {
            return $props;
        }

        $props = [];
        foreach ($this->getProperties() as $key => $property) {
            if (false === $property->isSearchProperty()) {
                continue;
            }
            $props[$key] = $property;
        }
        return $props;
    }

    /**
     * Determines if a property (attribute or relationship) is indexed for search.
     *
     * @param   string  $key    The property key.
     * @return  bool
     */
    public function propertySupportsSearch($key)
    {
        return isset($this->getSearchProperties()[$key]);
    }
}
