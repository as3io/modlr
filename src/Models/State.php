<?php

namespace As3\Modlr\Models;

use As3\Modlr\Store;
use As3\Modlr\Metadata\EntityMetadata;

/**
 * Represents a data record from a persistence (database) layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class State
{
    private $status = [
        'empty'     => false,
        'loaded'    => false,
        'dirty'     => false,
        'deleting'  => false,
        'deleted'   => false,
        'new'       => false,
    ];

    public function __construct()
    {
        $this->setEmpty();
    }

    public function is($status)
    {
        return $this->status[$status];
    }

    public function setDeleted($bit = true)
    {
        $this->set('deleted', $bit);
        if ($this->is('deleted')) {
            $this->setDeleting(false);
            $this->setDirty(false);
        }
    }

    public function setDeleting($bit = true)
    {
        $this->set('deleting', $bit);
    }

    public function setNew($bit = true)
    {
        $this->setLoaded();
        $this->set('new', $bit);
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
