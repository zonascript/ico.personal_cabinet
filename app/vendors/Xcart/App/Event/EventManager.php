<?php
namespace Xcart\App\Event;

use InvalidArgumentException;
use SplPriorityQueue;
use Xcart\App\DataClasses\DataPriorityQueue;

/**
 * Class EventManager
 * @package Xcart\App\Event
 */
class EventManager
{
    /**
     * @var SplPriorityQueue|DataPriorityQueue Events queue
     */
    protected $_events;

    /**
     * @var array List events from config
     */
    public $events = [];

    public function __construct()
    {
//        $this->_events = new SplPriorityQueue;
        $this->_events = new DataPriorityQueue();
    }

    public function init()
    {
        if ($this->events) {
            foreach ($this->events as $name => $group)
            {
                foreach ($group as $event) {
                    $this->on($name, $event['callback'], (isset($event['sender']) ? $event['sender'] : null), (isset($event['priority'])? $event['priority'] : 0));
                }
            }
        }
    }

    /**
     * @param $name string Event name
     * @param $callback callable Callback function
     * @param null $sender string|null Class name of sender or null
     * @param int $priority
     */
    public function on($name, $callback, $sender = null, $priority = 0)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Attribute $callback must be valid callback');
        }
        if (!is_string($sender) && !is_null($sender)) {
            throw new InvalidArgumentException('Attribute $sender must be string or null');
        }
        $this->_events->insert([
            'name' => $name,
            'callback' => $callback,
            'sender' => $sender
        ], $priority);
    }

    /**
     * @param $name string Event name
     * @param array $params Params that passes to callback function
     * @param null $sender string|object|null Sender object or sender class name or null
     * @param null $callback callable|null Callback function that calls after event callback function and takes result of event callback function
     */
    public function trigger($name, $params = array(), $sender = null, $callback = null)
    {
        if (!is_callable($callback) && !is_null($callback)) {
            throw new InvalidArgumentException('Attribute $callback must be valid callback or null');
        }

        foreach ($this->_events as $event) {
            if ($event['name'] == $name) {
                $receiver = $event['sender'];
                if ($sender && $receiver) {
                    if (
                        is_string($sender) &&
                        (!is_subclass_of($sender, $receiver) && $receiver !== $sender)
                    ) {
                        continue;
                    } elseif (
                        is_object($sender)
                        && !($sender instanceof $receiver)
                    ) {
                        continue;
                    }
                }
                $result = call_user_func_array($event['callback'], array_merge([$sender], $params));
                if ($callback) {
                    call_user_func_array($callback, [$result]);
                }
            }
        }
    }
}