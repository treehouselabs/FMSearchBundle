<?php

namespace FM\SearchBundle\Mapping;

use Solarium\QueryType\Select\Query\Query;

use FM\SearchBundle\Mapping\Facet\Type;
use FM\SearchBundle\Mapping\Config;

class Facet
{
    private $type;
    private $name;
    private $config;
    private $filter;

    public function __construct(Type $type, $name, array $config)
    {
        $this->type = $type;
        $this->name = $name;
        $this->config = new Config($config);
    }

    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function create(Query $query)
    {
        $this->type->create($this, $query);
    }
}
