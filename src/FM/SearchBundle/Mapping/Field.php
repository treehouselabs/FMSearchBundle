<?php

namespace FM\SearchBundle\Mapping;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;

use FM\SearchBundle\Mapping\Field\Type;

class Field
{
    private $type;
    private $name;
    private $accessor;
    private $accessorType;
    private $propertyPath;
    private $boost;
    private $required;
    private $multiValued;

    /**
     * Constructor
     *
     * @param Type                      $type
     * @param string                    $name
     * @param PropertyAccessorInterface $accessor
     * @param string|null               $propertyPath
     */
    public function __construct(Type $type, $name, PropertyAccessorInterface $accessor, $propertyPath = null)
    {
        $this->type = $type;
        $this->name = $name;
        $this->accessor = $accessor;
        $this->propertyPath = $propertyPath ?: $this->name;
        $this->required = false;
        $this->multiValued = false;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the value for this field of a given entity. Uses the accessor to
     * obtain the value.
     *
     * @param  string $entity
     * @return mixed
     */
    public function getValue($entity)
    {
        try {
            return $this->accessor->getValue($entity, $this->propertyPath);
        } catch (UnexpectedTypeException $e) {
            // This mostly happens when using a path more than 1 level deep,
            // and somewhere in the path, the value is empty. For instance when
            // getting the id of an association that is not set.
        }
    }

    /**
     * @param boolean $bool
     */
    public function setRequired($bool = true)
    {
        $this->required = (bool) $bool;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param boolean $multiValued
     */
    public function setMultiValued($multiValued)
    {
        $this->multiValued = (bool) $multiValued;
    }

    /**
     * @return boolean
     */
    public function isMultiValued()
    {
        return $this->multiValued;
    }

    /**
     * @param float $boost
     */
    public function setBoost($boost)
    {
        $this->boost = (float) $boost;
    }

    /**
     * @return float
     */
    public function getBoost()
    {
        return $this->boost;
    }

    /**
     * @param PropertyAccessorInterface $accessor
     */
    public function setAccessor(PropertyAccessorInterface $accessor)
    {
        $this->accessor = $accessor;
    }

    /**
     * @return PropertyAccessorInterface
     */
    public function getAccessor()
    {
        return $this->accessor;
    }

    /**
     * @param string $type
     */
    public function setAccessorType($type)
    {
        $this->accessorType = $type;
    }

    /**
     * @return string
     */
    public function getAccessorType()
    {
        return $this->accessorType;
    }

    /**
     * @param string $path
     */
    public function setPropertyPath($path)
    {
        $this->propertyPath = $path;
    }

    /**
     * @return string
     */
    public function getPropertyPath()
    {
        return $this->propertyPath;
    }

    /**
     * Makes sure the accessor instance is not serialized.
     *
     * @return array
     */
    public function __sleep()
    {
        $properties = get_class_vars(get_class($this));
        unset($properties['accessor']);

        return array_keys($properties);
    }
}
