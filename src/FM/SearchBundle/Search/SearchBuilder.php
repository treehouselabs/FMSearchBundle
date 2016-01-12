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
    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var array
     */
    private $options;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var string
     */
    private $query;

    /**
     * @var array
     */
    private $filters = array();

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Registry                 $registry
     * @param Schema                   $schema
     * @param array                    $options
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(Registry $registry, Schema $schema, array $options, EventDispatcherInterface $dispatcher)
    {
        $this->registry   = $registry;
        $this->schema     = $schema;
        $this->options    = $options;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return Schema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Sets default search query. This is to limit the result set which is
     * eventually filtered. Use this when you want to restrict the set of
     * documents that you want to search in.
     *
     * @param string $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @param string $name    The filter name
     * @param string $type    The filter type, one of the FacetType constants
     * @param array  $options The filter options
     */
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

            if (!isset($facet['count'])) {
                $facet['count'] = Facet::COUNT_TYPE_EXACT;
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

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param  string $name
     * @throws \OutOfBoundsException When filter does not exist
     */
    public function getFilter($name)
    {
        if (!isset($this->filters[$name])) {
            throw new \OutOfBoundsException(sprintf('Filter with name "%s" does not exist', $name));
        }

        return $this->filters[$name];
    }

    /**
     * @param  string $name
     * @throws \OutOfBoundsException When filter does not exist
     */
    public function removeFilter($name)
    {
        if (!isset($this->filters[$name])) {
            throw new \OutOfBoundsException(sprintf('Filter with name "%s" does not exist', $name));
        }

        unset($this->filters[$name]);
    }

    /**
     * @return Search
     */
    public function getSearch()
    {
        $search = new Search($this->schema);

        $this->applyQuery($search);
        $this->applyFilters($search);

        return $search;
    }

    /**
     * Applies the default query to the search, see setQuery.
     *
     * @param  Search $search
     */
    protected function applyQuery(Search $search)
    {
        if ($this->query) {
            $search->setQuery($this->query);
        }
    }

    /**
     * Applies the filters to the search
     *
     * @param  Search $search
     */
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

    /**
     * @param  Field  $field
     * @param  string $name
     * @param  string $type
     * @param  array  $options
     * @param  array  $facetConfig
     * @return Filter
     */
    protected function createFilter(Field $field, $name, $type, array $options, array $facetConfig = null)
    {
        $filterType = $this->registry->getFilterType($type);
        $filter = new Filter($name, $field, $filterType, $options);

        if (!empty($facetConfig)) {
            $type = $facetConfig['type'];
            $count = $facetConfig['count'];
            $options = $facetConfig['options'];
            $facetName = isset($facetConfig['name']) ? $facetConfig['name'] : $name;

            if (!isset($options['field'])) {
                $options['field'] = $field->getName();
            }

            $facet = $this->createFacet($type, $facetName, $count, $options);
            $filter->setFacet($facet);
        }

        return $filter;
    }

    /**
     * @param  string $type
     * @param  string $name
     * @param  string $count
     * @param  array  $options
     * @return Facet
     */
    protected function createFacet($type, $name, $count, array $options)
    {
        $facetType = $this->registry->getFacetType($type);

        return new Facet($facetType, $name, $count, $options);
    }
}
