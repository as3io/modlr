<?php

namespace Actinoids\Modlr\RestOdm\Events;

/**
 * Handles and dispatches events.
 * Listeners are registered with the manager and events are dispatched through this service.
 * Events include model/store lifecycle, and persister events.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class EventDispatcher
{
    /**
     * All registered event listener objects and their assigned events.
     *
     * @var array
     */
    private $listeners = [];

    /**
     * Adds an event listener.
     *
     * @param   string|array    $eventNames The event name(s) to listen for.
     * @param   object          $listener   The event listener object.
     * @return  self
     * @throws  \InvalidArgumentException   If the listener does not contain the event method.
     */
    public function addListener($eventNames, $listener)
    {
        $key = $this->getListenerKey($listener);
        foreach ((Array) $eventNames as $eventName) {
            if (!method_exists($listener, $eventName)) {
                throw new \InvalidArgumentException(sprintf('The listener class %s does not have the appropriate event method. Expected method "%s"', get_class($listener), $eventName));
            }
            $this->listeners[$eventName][$key] = $listener;
        }
        return $this;
    }

    /**
     * Adds an event subscriber.
     *
     * @param   EventSubscriberInterface    $subscriber
     * @return  self
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->addListener($subscriber->getEvents(), $subscriber);
    }

    /**
     * Dispatches an event to all registered listeners.
     *
     * @param   EventArguments|null     $arguments
     * @return  self
     */
    public function dispatch($eventName, EventArguments $arguments = null)
    {
        if (false === $this->hasListeners($eventName)) {
            return $this;
        }
        $arguments = $arguments ?: EventArguments::createEmpty();
        foreach ($this->getListeners($eventName) as $listener) {
            $listener->$eventName($arguments);
        }
        return $this;
    }

    /**
     * Gets the key for a listener object.
     *
     * @param   object  $listener
     * @return  string
     * @throws  \InvalidArgumentException   If the listener is not an object.
     */
    protected function getListenerKey($listener)
    {
        if (!is_object($listener)) {
            throw new \InvalidArgumentException('Event listeners must be an object.');
        }
        return spl_object_hash($listener);
    }

    /**
     * Gets all registered listeners for an event name.
     *
     * @param   string  $eventName
     * @return  array|null
     */
    protected function getListeners($eventName)
    {
        if (isset($this->listeners[$eventName])) {
            return $this->listeners[$eventName];
        }
        return null;
    }

     /**
     * Determines if registered listeners exist for an event name.
     *
     * @param   string  $eventName
     * @return  bool
     */
    public function hasListeners($eventName)
    {
        return null !== $this->getListeners($eventName);
    }

    /**
     * Removes an event listener, if registered.
     *
     * @param   string|array    $eventNames The event name(s) to listen for.
     * @param   object          $listener   The event listener object.
     * @return  self
     */
    public function removeListener($eventNames, $listener)
    {
        $key = $this->getListenerKey($listener);
        foreach ((Array) $eventNames as $eventName) {
            if (isset($this->listeners[$eventName][$key])) {
                unset($this->listeners[$eventName][$key]);
            }
        }
        return $this;
    }

    /**
     * Removes an event subscriber.
     *
     * @param   EventSubscriberInterface    $subscriber
     * @return  self
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->removeListener($subscriber->getEvents(), $subscriber);
    }
}
