<?php

namespace Dan\Events;

use Illuminate\Support\Collection;

class Handler
{
    /**
     * @var Collection
     */
    protected $events;

    /**
     * @var Collection
     */
    protected $names;

    /**
     * @var Collection
     */
    protected $priorities;

    /**
     * @var Collection
     */
    protected $addonEvents;

    /**
     * Handler constructor.
     */
    public function __construct()
    {
        $this->events = new Collection();
        $this->names = new Collection();
        $this->priorities = new Collection();
        $this->addonEvents = new Collection();

        $this->subscribe('addons.load', function() {
            foreach ($this->addonEvents as $id => $name) {
                $this->destroy($this->events[$id]);
            }

            $this->addonEvents = new Collection();
        });
    }

    /**
     * @param $name
     * @param $handler
     * @param $priority
     *
     * @return \Dan\Events\Event
     */
    public function subscribe($name, $handler = null, $priority = Event::Normal) : Event
    {
        $event = new Event($name, $handler, $priority);

        $this->events->put($event->id, $event);
        $this->names->put($event->id, $name);
        $this->priorities->put($event->id, $priority);

        console()->debug("Creating event {$name} - ID: {$event->id} - Priority: {$priority}");

        return $event;
    }

    /**
     * Registers an addon event that will be automatically destroyed when addons are reloaded.
     *
     * @param $name
     *
     * @return \Dan\Events\Event
     */
    public function registerAddonEvent($name) : Event
    {
        console()->info("Registering addon event handler for {$name}");
        $event = $this->subscribe($name);
        $this->addonEvents->put($event->id, $name);

        return $event;
    }

    /**
     * @param $name
     * @param array $args
     *
     * @return mixed
     */
    public function fire($name, $args = [])
    {
        console()->debug("Firing all subscriptions to event {$name}");

        $keys = $this->names->filter(function ($item) use ($name) {
            if ($item == $name) {
                return $item;
            }
        })->keys()->toArray();

        if (empty($keys)) {
            return null;
        }

        $priorities = $this->priorities->only($keys)->toArray();

        arsort($priorities);

        foreach ($priorities as $key => $priority) {
            /** @var Event $event */
            $event = $this->events->get($key);

            console()->debug("Calling event {$this->names[$key]} - ID: {$key}");

            $result = $event->call($args);

            if ($result === false) {
                return false;
            }

            if ($result instanceof EventArgs) {
                $args = $result;
                continue;
            }
        }

        return $args;
    }

    /**
     * Destroys an event by object or id.
     *
     * @param $event
     */
    public function destroy($event)
    {
        if ($event instanceof Event) {
            $event = $event->id;
        }

        console()->debug("Destroying event {$this->names->get($event)} - ID: {$event}");
        $this->events->forget($event);
        $this->names->forget($event);
        $this->priorities->forget($event);
        $this->addonEvents->forget($event);
    }
}
