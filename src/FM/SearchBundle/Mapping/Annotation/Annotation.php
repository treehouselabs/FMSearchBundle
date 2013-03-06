<?php

namespace FM\SearchBundle\Mapping\Annotation;

abstract class Annotation
{
    private $values;

    public function __construct(array $values)
    {
        $this->values = $values;
        $this->validate();
    }

    protected function validate()
    {
    }

    public function has($name)
    {
        return array_key_exists($name, $this->values);
    }

    public function get($name, $default = null)
    {
        return array_key_exists($name, $this->values) ? $this->values[$name] : $default;
    }

    public function getBool($name, $default = null)
    {
        return (bool) $this->get($name, $default);
    }

    public function getInt($name, $default = null)
    {
        return (int) $this->get($name, $default);
    }
}
