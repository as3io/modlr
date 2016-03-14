<?php

namespace As3\Modlr\Events;

/**
 * Contains the arguments for an event.
 * This is the root class and does not contain any event data.
 * All event arguments must extend this class and define their own state.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class EventArguments
{
    /**
     * The globally empty event instance.
     * Used when no event arguments are passed to the dispatcher.
     *
     * @var self
     */
    private static $emptyInstance;

    /**
     * Creates an empty arguments instance.
     * Ensures only one empty instances exists for all possible event calls without arguments.
     *
     * @return  self
     */
    public static function createEmpty()
    {
        if (null === self::$emptyInstance) {
            self::$emptyInstance = new self;
        }
        return self::$emptyInstance;
    }
}
