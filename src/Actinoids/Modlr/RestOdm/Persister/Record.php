<?php

namespace Actinoids\Modlr\RestOdm\Persister;

use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;

/**
 * Persists and retrieves models to/from a MongoDB database.
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
}
