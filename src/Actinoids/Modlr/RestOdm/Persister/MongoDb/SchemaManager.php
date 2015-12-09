<?php

namespace Actinoids\Modlr\RestOdm\Persister\MongoDb;

class SchemaManager
{
    private $loadedIndexes = [];

    public function createIndexesForType($typeKey)
    {

    }

    public function retrieveIndexesForType(EntityMetadata $metadata)
    {
        if (isset($this->loadedIndexes[$metadata->type])) {
            return $this->loadedIndexes[$metadata->type];
        }
        var_dump(__METHOD__);
        die();
    }

    public function updateIndexesForType($typeKey)
    {

    }

    public function deleteIndexesForType($typeKey)
    {

    }

    public function createDatabaseForType($typeKey)
    {

    }

    public function dropDatabaseForType($typeKey)
    {

    }

    public function createCollectionForType($typeKey)
    {

    }

    public function dropCollectionForType($typeKey)
    {

    }
}
