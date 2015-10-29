<?php

namespace Actinoids\Modlr\RestOdm\Models;

use Actinoids\Modlr\RestOdm\Store\Store;
use Actinoids\Modlr\RestOdm\Metadata\EntityMetadata;

/**
 * Represents the attributes of a Model.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Attributes
{
    private $original = [];

    private $current = [];

    private $remove = [];

    public function __construct(array $original = [])
    {
        $this->original = $original;
    }

    public function intialize(array $original)
    {
        $this->rollback();
        $this->original = $original;
        return $this;
    }

    public function get($key)
    {
        if ($this->willRemove($key)) {
            return null;
        }
        if (true === $this->willChange($key)) {
            return $this->getCurrent($key);
        }
        return $this->getOriginal($key);
    }

    public function set($key, $value)
    {
        if (null === $value) {
            return $this->remove($key);
        }
        $this->clearRemoval($key);

        if ($value === $this->getOriginal($key)) {
            $this->clearChange($key);
        } else {
            $this->current[$key] = $value;
        }
        return $this;
    }

    public function remove($key)
    {
        if (false === $this->willRemove($key)) {
            $this->remove[] = $key;
            $this->clearChange($key);
        }
        return $this;
    }

    public function rollback()
    {
        $this->current = [];
        $this->remove = [];
        return $this;
    }

    public function areDirty()
    {
        return !empty($this->current) || !empty($this->remove);
    }

    protected function clearRemoval($key)
    {
        if (false === $this->willRemove($key)) {
            return $this;
        }
        $key = array_search($key, $this->remove);
        unset($this->remove[$key]);
        $this->remove = array_values($this->remove);
        return $this;
    }

    protected function clearChange($key)
    {
        if (true === $this->willChange($key)) {
            unset($this->current[$key]);
        }
        return $this;
    }

    protected function willRemove($key)
    {
        return in_array($key, $this->remove);
    }

    protected function willChange($key)
    {
        return null !== $this->getCurrent($key);
    }

    protected function getOriginal($key)
    {
        if (isset($this->original[$key])) {
            return $this->original[$key];
        }
        return null;
    }

    protected function getCurrent($key)
    {
        if (isset($this->current[$key])) {
            return $this->current[$key];
        }
        return null;
    }
}
