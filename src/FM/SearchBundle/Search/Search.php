<?php

namespace FM\SearchBundle\Search;

use FM\SearchBundle\Mapping\Filter;
use FM\SearchBundle\Mapping\Facet;
use FM\SearchBundle\Mapping\Schema;

class Search
{
    private $schema;
    private $query;
    private $filters;
    private $facets;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
        $this->filters = new Set;
        $this->facets = new Set;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function addFilter(Filter $filter)
    {
        $this->filters->add($filter->getName(), $filter);
    }

    public function hasFilter($name)
    {
        return $this->filters->has($name);
    }

    public function getFilter($name)
    {
        return $this->filters->get($name);
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function addFacet(Facet $facet)
    {
        $this->facets->add($facet->getName(), $facet);
    }

    public function getFacets()
    {
        return $this->facets;
    }
}
