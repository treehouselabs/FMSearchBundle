<?php

namespace FM\SearchBundle\Mapping;

class Config implements \ArrayAccess
{
    private $data;

    public function __construct(array $data = array())
    {
        $this->data = $data;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
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
     * @param  string $name
     * @param  string $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if (!$this->has($name)) {
            return $default;
        }

        return $this->data[$name];
    }

    public function all()
    {
        return $this->data;
    }
}
