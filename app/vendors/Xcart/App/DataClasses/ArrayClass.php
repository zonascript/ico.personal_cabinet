<?php
namespace Xcart\App\DataClasses;

use ArrayAccess;
use Countable;
use Iterator;
use Serializable;

class ArrayClass implements ArrayAccess, Iterator, Countable, Serializable
{

    protected $data = [];

    public function getData($key)
    {
        return $this->data[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return !empty($this->data[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->getData($offset);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function current()
    {
        return $this->getData($this->key());
    }

    public function next()
    {
        next($this->data);
    }

    public function key()
    {
        return key($this->data);
    }

    public function valid()
    {
        return $this->offsetExists($this->key());
    }

    public function rewind()
    {
        reset($this->data);
    }

    public function count()
    {
        return count($this->data);
    }

    public function serialize()
    {
        return serialize($this->data);
    }

    public function unserialize($serialized)
    {
        $this->data = unserialize($serialized);
    }
}