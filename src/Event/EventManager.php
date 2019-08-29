<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Origin\Event;

class EventManager
{
    /**
     * @var \Origin\Event\EventManager
     */
    private static $instance;

    /**
     * Holds the listeners
     *
     * @var array
     */
    protected $listeners = [];

    /**
     * Gets the instance of the EventDispatcher
     *
     * @return void
     */
    public static function instance() : EventManager
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Creates a new event
     *
     * @param string $name
     * @param object $subject
     * @param mixed $data
     * @return \Origin\Event\Event;
     */
    public function new(string $name, object $subject = null, $data = null) : Event
    {
        return new Event($name, $subject, $data);
    }

    /**
     * Dispatches an event. If the event returns false then it is stopped.
     *
     * $event = $eventManager->new('Order.afterPurchase);
     * $eventManger->dispatch($event);
     *
     * OR
     *
     * $eventManager->dispatch('Order.afterPurchase');
     *
     *
     * @param Event|string $event
     * @return \Origin\Event\Event;
     */
    public function dispatch($event) : Event
    {
        if (is_string($event)) {
            $event = new Event($event);
        }
        $listeners = $this->listeners($event->name());

        if (empty($listeners)) {
            return $event;
        }
        
        foreach ($listeners as $listener) {
            if ($event->isStopped()) {
                break;
            }
         
            $result = call_user_func($listener, $event);
            
            if ($result === false) {
                $event->stop();
            }
            if ($result !== null) {
                $event->result($result);
            }
        }

        return $event;
    }

    /**
     * Adds a listener
     *
     * @param string $name [$this,'sendEmail']
     * @param callable|array $callable [$this,'someMethod'], new SlackNotification()
     * @param integer $priority
     * @return void
     */
    public function listen(string $name, $callable, int $priority = 10) : void
    {
        if (! is_callable($callable) and is_object($callable)) {
            $callable = [$callable,'execute'];
        }
        if (empty($this->listeners[$name])) {
            $this->listeners[$name][$priority] = [];
        }

        $this->listeners[$name][$priority][] = $callable;
    }

    /**
     * Returns the listeners for an event ordered by priority
     *
     * @param string $name
     * @return array array of callables
     */
    protected function listeners(string $name) : array
    {
        $listeners = [];
        if (isset($this->listeners[$name])) {
            ksort($this->listeners[$name]);
            foreach ($this->listeners[$name] as $queue) {
                $listeners = array_merge($listeners, $queue);
            }
        }
        
        return $listeners;
    }

    /**
     * Attach multiple listeners of an object, the object should have a method
     * implementedEvents which returns an array.
     *
     * Example:
     * class Foo {
     *  function implementedEvents(){
     *     return ['Controller.initialize'=>'initialize']
     *  }
     * }
     *
     * $manager->subscribe(new Foo());
     *
     * @param object $subscriber
     * @return void
     */
    public function subscribe(object $subscriber) : void
    {
        foreach ($subscriber->implementedEvents() as $key => $function) {
            $this->listen($key, [$subscriber,$function]);
        }
    }
}
