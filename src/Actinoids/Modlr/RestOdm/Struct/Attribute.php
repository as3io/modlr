<?php

namespace Actinoids\Modlr\RestOdm\Struct;

class Attribute
{
    /**
     * The attribute key (field name).
     *
     * @var string
     */
    protected $key;

    /**
     * The attribute value.
     *
     * @var string
     */
    protected $value;

    /**
     * Constructor.
     *
     * @param   string  $key
     * @param   mixed   $value
     */
    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * Gets the attribute key (field) name
     *
     * @return  string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Gets the attribute value.
     *
     * @return  mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
