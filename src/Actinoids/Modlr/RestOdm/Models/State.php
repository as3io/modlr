<?php

namespace Actinoids\Modlr\RestOdm\Models;

use Actinoids\Modlr\RestOdm\Store;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;

/**
 * Represents a data record from a persistence (database) layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class State
{
    private $status = [
        'empty'     => false,
        'loading'   => false,
        'loaded'    => false,
        'dirty'     => false,
        'saving'    => false,
        'deleted'   => false,
        'new'       => false,
    ];

    public function __construct()
    {
        $this->setEmpty();
    }

    public function setNew()
    {
        $this->setLoaded();
        $this->setDirty();
        $this->set('new', true);
    }

    public function setLoaded($bit = true)
    {
        $this->setEmpty(false);
        $this->set('loaded', $bit);
    }

    public function setDirty($bit = true)
    {
        $this->set('dirty', $bit);
    }

    public function setEmpty($bit = true)
    {
        $this->set('empty', $bit);
    }

    protected function set($key, $bit)
    {
        $this->status[$key] = (Boolean) $bit;
    }
}
