<?php

namespace FM\SearchBundle\Mapping;

class Schema
{
    /**
     * @var string
     */
    private $client;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $uniqueKey;

    /**
     * @var Field[]
     */
    private $fields;

    /**
     * @var string
     */
    private $repositoryClass;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->fields = array();
    }

    /**
     * @param string $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return string
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $key
     */
    public function setUniqueKey($key)
    {
        $this->uniqueKey = $key;
    }

    /**
     * @return string
     */
    public function getUniqueKey()
    {
        return $this->uniqueKey;
    }

    /**
     * @param Field $field
     */
    public function addField(Field $field)
    {
        $this->fields[$field->getName()] = $field;
    }

    /**
     * @return boolean
     */
    public function hasField($name)
    {
        return array_key_exists($name, $this->fields);
    }

    /**
     * @return Field[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return Field
     * @throws \OutOfBoundsException If field does not exist
     */
    public function getField($name)
    {
        if (!$this->hasField($name)) {
            throw new \OutOfBoundsException(sprintf('Field %s does not exist', $name));
        }

        return $this->fields[$name];
    }

    /**
     * @return Field
     */
    public function getUniqueKeyField()
    {
        return $this->getField($this->getUniqueKey());
    }

    /**
     * @param string $class
     */
    public function setRepositoryClass($class)
    {
        $this->repositoryClass = $class;
    }

    /**
     * @return string
     */
    public function getRepositoryClass()
    {
        return $this->repositoryClass;
    }
}
