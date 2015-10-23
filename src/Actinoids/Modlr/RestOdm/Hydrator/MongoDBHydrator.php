<?php

namespace Actinoids\Modlr\RestOdm\Hydrator;

class MongoDBHydrator extends AbstractHydrator
{
    /**
     * {@inheritDoc}
     */
    public function getIdKey()
    {
        return '_id';
    }

    /**
     * {@inheritDoc}
     */
    public function getPolymorphicKey()
    {
        return '_type';
    }
}
