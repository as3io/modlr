<?php

namespace Actinoids\Modlr\RestOdm\Struct;

class Relationship extends Resource
{
    /**
     * The relationship key (field name).
     *
     * @var string
     */
    protected $key;

    /**
     * Constructor.
     *
     * @param   string  $key
     * @param   string  $entityType
     * @param   string  $resourceType
     */
    public function __construct($key, $entityType, $resourceType = 'one')
    {
        parent::__construct($entityType, $resourceType);
        $this->key = $key;
    }

    /**
     * Gets the relationship key (field) name
     *
     * @return  string
     */
    public function getKey()
    {
        return $this->key;
    }
}
