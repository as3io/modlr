<?php

namespace As3\Modlr\RestOdm\Metadata\Interfaces;

use As3\Modlr\RestOdm\Metadata\AttributeMetadata;

/**
 * Interface for Metadata objects containing attributes.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface AttributeInterface extends PropertyInterface
{
    /**
     * Adds an attribute field to the implementing metadata object.
     *
     * @param   AttributeMetadata   $attribute
     * @return  self
     */
    public function addAttribute(AttributeMetadata $attribute);

    /**
     * Gets all attribute fields for the implementing metadata object.
     *
     * @return  AttributeMetadata[]
     */
    public function getAttributes();

    /**
     * Determines if any attribute fields exist on the implementing metadata object.
     *
     * @return  bool
     */
    public function hasAttributes();

    /**
     * Determines if an attribute field exists on the implementing metadata object.
     *
     * @param   string  $key
     * @return  bool
     */
    public function hasAttribute($key);

    /**
     * Gets an attribute field from the implementing metadata object.
     * Returns null if the attribute does not exist.
     *
     * @param   string  $key
     * @return  AttributeMetadata|null
     */
    public function getAttribute($key);
}
