<?php

namespace FM\SearchBundle\Search;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use FM\SearchBundle\Mapping\Registry;
use FM\SearchBundle\Mapping\Schema;
use FM\SearchBundle\Mapping\Field;
use FM\SearchBundle\Mapping\Filter;
use FM\SearchBundle\Mapping\Facet;
use FM\SearchBundle\Mapping\Filter\Type as FilterType;
use FM\SearchBundle\Mapping\Facet\Type as FacetType;

class SearchBuilder implements SearchBuilderInterface
{
    private $schema;
    private $options;
    private $dispatcher;

    private $query;
    private $filters = array();

    public function __construct(Registry $registry, Schema $schema, array $options, EventDispatcherInterface $dispatcher)
    {
        $this->registry   = $registry;
        $this->schema     = $schema;
        $this->options    = $options;
        $this->dispatcher = $dispatcher;
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function addFilter($name, $type = null, array $options = array())
    {
        if (!isset($options['facet'])) {
            $options['facet'] = array();
        }

        if ($options['facet'] === false) {

            $facet = null;

        } else {

            $facet = (array) $options['facet'];

            if (!isset($facet['type'])) {
                $facet['type'] = FacetType::FIELD;
            }

            if (!isset($facet['options'])) {
                $facet['options'] = array();
            }
        }

        $this->filters[$name] = array(
            'type'    => $type ?: FilterType::EQUALS,
            'options' => $options,
            'facet'   => $facet
        );
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function getSearch()
    {
        $search = new Search($this->schema);

        $this->applyQuery($search);
        $this->applyFilters($search);

        return $search;
    }

    protected function applyQuery(Search $search)
    {
        if ($this->query) {
            $search->setQuery($this->query);
        }
    }

    protected function applyFilters(Search $search)
    {
        foreach ($this->filters as $name => $config) {

            $type = $config['type'];
            $options = $config['options'];

            $fieldName = isset($options['field']) ? $options['field'] : $name;
            $field = $this->schema->getField($fieldName);

            $filter = $this->createFilter($field, $name, $type, $options, $config['facet']);

            $search->addFilter($filter);
        }
    }

    protected function createFilter(Field $field, $name, $type, array $options, array $facetConfig = null)
    {
        $filterType = $this->registry->getFilterType($type);
        $filter = new Filter($name, $field, $filterType, $options);

        if (!empty($facetConfig)) {

            $type = $facetConfig['type'];
            $options = $facetConfig['options'];
            $facetName = isset($facetConfig['name']) ? $facetConfig['name'] : $name;

            if (!isset($options['field'])) {
                $options['field'] = $field->getName();
            }

            $facet = $this->createFacet($type, $facetName, $options);
            $filter->setFacet($facet);
        }

        return $filter;
    }

    protected function createFacet($type, $name, array $options)
    {
        $facetType = $this->registry->getFacetType($type);

        return new Facet($facetType, $name, $options);
    }
}
