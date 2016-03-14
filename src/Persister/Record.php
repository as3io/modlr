<?php

namespace As3\Modlr\RestOdm\Persister;

use As3\Modlr\RestOdm\Metadata\EntityMetadata;

/**
 * Represents a record from the persistence/data layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Record
{
    private $id;

    private $type;

    private $properties = [];

    public function __construct($type, $id, array $properties)
    {
        $this->type = $type;
        $this->id = (String) $id;
        $this->properties = $properties;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function toArray()
    {
        return [
            'id'    => $this->getId(),
            'type'  => $this->getType(),
            'properties' => $this->getProperties(),
        ];
    }
}
