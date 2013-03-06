<?php

namespace FM\SearchBundle\Search;

use Iterator;

/**
 * Basic key-value set.
 */
class Set implements Iterator
{
    private $data = array();

    public function current()
    {
        return current($this->data);
    }

    public function key()
    {
        return key($this->data);
    }

    public function next()
    {
        return next($this->data);
    }

    public function rewind()
    {
        return reset($this->data);
    }

    public function valid()
    {
        return key($this->data) !== null;
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function add($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * @param  string  $name
     * @return boolean
     */
    public function has($name)
    {
        return array_key_exists($name, $this->data);
    }

    /**
     * @param  string                $name
     * @return mixed
     * @throws \OutOfBoundsException
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new \OutOfBoundsException(sprintf('Set doesn\'t contain key "%s"', $name));
        }

        return $this->data[$name];
    }
}
