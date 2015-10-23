<?php

namespace Actinoids\Modlr\RestOdm\Hydrator;

class JsonApiHydrator extends AbstractHydrator
{
    /**
     * {@inheritDoc}
     */
    public function getIdKey()
    {
        return 'id';
    }

    /**
     * {@inheritDoc}
     */
    public function getPolymorphicKey()
    {
        return 'type';
    }
}
