<?php

namespace As3\Modlr\Models\Embeds;

use As3\Modlr\Models\Attributes;
use As3\Modlr\Models\Embed;

/**
 * Represents the has-one embeds of a Model.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class HasOne extends Attributes
{
    /**
     * Constructor.
     *
     * @param   Embed[]   $original   Any original properties to apply.
     */
    public function __construct(array $original = [])
    {
        foreach ($original as $key => $embed) {
            if (!$embed instanceof Embed) {
                continue;
            }
            $this->current[$key] = clone $embed;
        }
        parent::__construct($original);
    }

    /**
     * {@inheritdoc}
     */
    public function areDirty()
    {
        if (!empty($this->remove)) {
            return true;
        }

        foreach ($this->getCurrentAll() as $current) {
            if (true === $current->isDirty()) {
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateChangeSet()
    {
        $set = [];
        foreach ($this->current as $key => $current) {
            if (false === $current->isDirty()) {
                continue;
            }
            $original = isset($this->original[$key]) ? $this->original[$key] : null;
            $set[$key]['old'] = $original;
            $set[$key]['new'] = $current;
        }

        foreach ($this->remove as $key) {
            $set[$key]['old'] = $this->original[$key];
            $set[$key]['new'] = null;
        }
        ksort($set);
        return $set;
    }

    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        $this->current = [];
        foreach ($this->original as $key => $embed) {
            if (!$embed instanceof Embed) {
                continue;
            }
            $this->current[$key] = clone $embed;
        }
        $this->remove = [];
        return $this;
    }
}
