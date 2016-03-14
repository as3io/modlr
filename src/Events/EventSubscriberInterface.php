<?php

namespace As3\Modlr\Events;

/**
 * Interface for event subscribers.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface EventSubscriberInterface
{
    /**
     * Returns an array of event names that this class subscribes to.
     * Each event must also have a corresponding method in this class.
     *
     * @return  array
     */
    public function getEvents();
}
