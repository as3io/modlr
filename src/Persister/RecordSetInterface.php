<?php

namespace As3\Modlr\Persister;

use \Countable;
use \Iterator;
use As3\Modlr\Metadata\EntityMetadata;

/**
 * Represents the implementation for handling record sets from the persistence/data layer.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
interface RecordSetInterface extends Countable, Iterator
{
    /**
     * Gets the 'found only' record count.
     *
     * @return  int
     */
    public function count();

    /**
     * Gets a single record from the collection.
     *
     * @return  array|null
     */
    public function getSingleResult();

    /**
     * Gets the 'total' record count, as if a limit and offset were not applied.
     *
     * @return  int
     */
    public function totalCount();
}
