<?php

namespace Actinoids\Modlr\RestOdm\Struct;

class Identifier implements EntityInterface
{
    use Traits\MetaEnabled;

    /**
     * The entity id.
     *
     * @var string
     */
    protected $id;

    /**
     * The entity type.
     *
     * @var string
     */
    protected $type;

    /**
     * Constructor.
     *
     * @param   string  $id
     * @param   string  $type
     */
    public function __construct($id, $type)
    {
        $this->id = (String) $id;
        $this->type = $type;
    }

    /**
     * {@inheritDoc}
     */
    public function getCompositeKey()
    {
        return sprintf('%s.%s', $this->getType(), $this->getId());
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return $this->type;
    }
}
