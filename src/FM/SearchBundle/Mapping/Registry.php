<?php

namespace FM\SearchBundle\Mapping;

use FM\SearchBundle\Mapping\Field\Type as FieldType;
use FM\SearchBundle\Mapping\Filter\Type as FilterType;
use FM\SearchBundle\Mapping\Facet\Type as FacetType;
use FM\SearchBundle\Mapping\Accessor\Type as AccessorType;

/**
 * Registry class that contains mappings for various types of fields, filters,
 * facets and property accessors. The type getters use cached instances, so a
 * type class is only used once, using a lazy loading mechanism.
 */
class Registry
{
    const EQUALS = 'equals';
    const RANGE  = 'range';

    private $mapping = array(
        'field'    => array(
            FieldType::STRING   => '\FM\SearchBundle\Mapping\Field\Type\String',
            FieldType::TEXT     => '\FM\SearchBundle\Mapping\Field\Type\Text',
            FieldType::BOOLEAN  => '\FM\SearchBundle\Mapping\Field\Type\Boolean',
            FieldType::INTEGER  => '\FM\SearchBundle\Mapping\Field\Type\Integer',
            FieldType::FLOAT    => '\FM\SearchBundle\Mapping\Field\Type\Float',
            FieldType::DATETIME => '\FM\SearchBundle\Mapping\Field\Type\DateTime',
            FieldType::LOCATION => '\FM\SearchBundle\Mapping\Field\Type\Location'
        ),
        'filter'   => array(
            FilterType::EQUALS  => '\FM\SearchBundle\Mapping\Filter\Type\Equals',
            FilterType::RANGE   => '\FM\SearchBundle\Mapping\Filter\Type\Range'
        ),
        'facet'    => array(
            FacetType::FIELD    => '\FM\SearchBundle\Mapping\Facet\Type\Field',
            FacetType::RANGE    => '\FM\SearchBundle\Mapping\Facet\Type\Range'
        ),
        'accessor' => array(
            AccessorType::GRAPH => '\Symfony\Component\PropertyAccess\PropertyAccessor',
            AccessorType::UUID  => '\FM\SearchBundle\PropertyAccess\Uuid'
        )
    );

    private $typeObjects = array(
    );

    /**
     * Adds a type to the registry.
     *
     * @param string $registry The registry to add this type to, ie: "field"
     *                           or "filter"
     * @param  string         $name      The type's name
     * @param  string         $className A FQCN to use when creating an instance
     * @throws LogicException When a type is already defined
     */
    public function addType($registry, $name, $className)
    {
        if (isset($this->mapping[$registry][$name])) {
            throw new \LogicException(sprintf('%s type "%s" is already defined', $registry, $name));
        }

        // make sure it's fully qualified
        if (substr($className, 0, 1) !== '\\') {
            $className = '\\' . $className;
        }

        $this->mapping[$registry][$name] = $className;
    }

    /**
     * Adds a type to the registry, using the supplied instance. Use this when
     * you need to manually create a type instance, or call some method on it
     * first.
     *
     * @param string $registry The registry to add this type to, ie: "field"
     *                           or "filter"
     * @param  string         $name     The type's name
     * @param  object         $instance An instance of the type
     * @throws LogicException When a type is already defined
     */
    public function registerType($registry, $name, $instance)
    {
        $this->addType($registry, $name, get_class($instance));
        $this->typeObjects[$registry][$name] = $instance;
    }

    /**
     * @param  string                   $registry
     * @param  string                   $name
     * @return object
     * @throws InvalidArgumentException When an unknown type is requested
     */
    public function getType($registry, $name)
    {
        if (!isset($this->typeObjects[$registry][$name])) {
            if (!isset($this->mapping[$registry][$name])) {
                throw new \InvalidArgumentException(sprintf('Unknown %s type "%s"', $registry, $name));
            }

            $this->typeObjects[$registry][$name] = new $this->mapping[$registry][$name]();
        }

        return $this->typeObjects[$registry][$name];
    }

    public function getFieldType($name)
    {
        return $this->getType('field', $name);
    }

    public function getFilterType($name)
    {
        return $this->getType('filter', $name);
    }

    public function getFacetType($name)
    {
        return $this->getType('facet', $name);
    }

    public function getAccessorType($name)
    {
        return $this->getType('accessor', $name);
    }

    /**
     * Reverse type lookup: supply an instance or FQCN and you'll get the name
     *
     * @param  string        $registry
     * @param  object|string $type     Type instance or FQCN
     * @return string|null
     */
    public function getTypeName($registry, $type)
    {
        if (is_object($type)) {
            $type = get_class($type);
        }

        if (substr($type, 0, 1) !== '\\') {
            $type = '\\' . $type;
        }

        return array_search($type, $this->mapping[$registry]) ?: null;
    }
}
