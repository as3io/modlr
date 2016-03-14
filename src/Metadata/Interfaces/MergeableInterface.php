<?php

namespace As3\Modlr\Metadata\Interfaces;

/**
 * Defines the metadata instances that support merging.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface MergeableInterface
{
    /**
     * Merges a Mergeable instance with this instance.
     * For use with entity class extension.
     * Only merge items where you want the child class to override the parent!
     *
     * @param   MergeableInterface  $metadata
     * @return  self
     */
    public function merge(MergeableInterface $metadata);
}
