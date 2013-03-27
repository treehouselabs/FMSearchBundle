<?php

namespace FM\SearchBundle\Mapping;

class Schema
{
    private $client;
    private $name;
    private $uniqueKey;
    private $fields;
    private $repositoryClass;

    public function __construct($name)
    {
        $this->name = $name;
        $this->fields = array();
    }

    public function setClient($client)
    {
        $this->client = $client;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setUniqueKey($key)
    {
        $this->uniqueKey = $key;
    }

    public function getUniqueKey()
    {
        return $this->uniqueKey;
    }

    public function addField(Field $field)
    {
        $this->fields[$field->getName()] = $field;
    }

    public function hasField($name)
    {
        return array_key_exists($name, $this->fields);
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getField($name)
    {
        if (!$this->hasField($name)) {
            throw new \OutOfBoundsException(sprintf('Field %s does not exist', $name));
        }

        return $this->fields[$name];
    }

    public function getUniqueKeyField()
    {
        return $this->getField($this->getUniqueKey());
    }

    public function setRepositoryClass($class)
    {
        $this->repositoryClass = $class;
    }

    public function getRepositoryClass()
    {
        return $this->repositoryClass;
    }
}
