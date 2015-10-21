<?php

namespace Actinoids\Modlr\RestOdm\Struct;

interface EntityInterface
{
    /**
     * Gets the unique, composite key of the entity
     * Combines the type with the id.
     *
     * @return  string
     */
    public function getCompositeKey();

    /**
     * Gets the entity id value.
     *
     * @return  string
     */
    public function getId();

    /**
     * Gets the entity type.
     *
     * @return  string
     */
    public function getType();
}
