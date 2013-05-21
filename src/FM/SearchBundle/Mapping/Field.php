<?php

namespace FM\SearchBundle\Mapping;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;

use FM\SearchBundle\Mapping\Field\Type;
use FM\SearchBundle\PropertyAccess\AccessorInterface;

class Field
{
    private $type;
    private $name;
    private $accessor;
    private $accessorType;
    private $boost;
    private $required;
    private $multiValued;

    public function __construct(Type $type, $name, PropertyAccessorInterface $accessor, $propertyPath = null)
    {
        $this->type = $type;
        $this->name = $name;
        $this->accessor = $accessor;
        $this->propertyPath = $propertyPath ?: $this->name;
        $this->required = false;
        $this->multiValued = false;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getName()
    {
        return $this->name;
    }

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

    public function setRequired($bool = true)
    {
        $this->required = (bool) $bool;
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function setMultiValued($multiValued)
    {
        $this->multiValued = (bool) $multiValued;
    }

    public function isMultiValued()
    {
        return $this->multiValued;
    }

    public function setBoost($boost)
    {
        $this->boost = (float) $boost;
    }

    public function getBoost()
    {
        return $this->boost;
    }

    public function setAccessor(PropertyAccessorInterface $accessor)
    {
        $this->accessor = $accessor;
    }

    public function getAccessor()
    {
        return $this->accessor;
    }

    public function setAccessorType($type)
    {
        $this->accessorType = $type;
    }

    public function getAccessorType()
    {
        return $this->accessorType;
    }

    /**
     * Makes sure the accessor instance is not serialized.
     *
     * @return array
     */
    public function __sleep()
    {
        return array(
            'type',
            'name',
            'accessorType',
            'boost',
            'required',
            'multiValued',
        );
    }
}
