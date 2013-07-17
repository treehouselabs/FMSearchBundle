<?php

namespace FM\SearchBundle\Mapping;

use Solarium\QueryType\Select\Query\Query;

use FM\SearchBundle\Mapping\Facet\Type;
use FM\SearchBundle\Mapping\Config;

class Facet
{
    /**
     * Use facet counts as returned by Solr
     */
    const COUNT_TYPE_EXACT      = 'exact';

    /**
     * Add facet counts on each value, creating cumulative counts. Useful for
     * facets like "1 or more, 10 or more, 20 or more, etc".
     */
    const COUNT_TYPE_CUMULATIVE = 'cumulative';

    protected $type;
    protected $name;
    protected $countType;
    protected $config;
    protected $filter;

    public function __construct(Type $type, $name, $countType, array $config)
    {
        $this->type      = $type;
        $this->name      = $name;
        $this->config    = new Config($config);

        $this->setCountType($countType);
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

    public function setCountType($type)
    {
        $types = array(self::COUNT_TYPE_EXACT, self::COUNT_TYPE_CUMULATIVE);

        if (!in_array($type, $types)) {
            throw new \InvalidArgumentException(sprintf('One of %s count types is expected', json_encode($types)));
        }

        $this->countType = $type;
    }

    public function getCountType()
    {
        return $this->countType;
    }

    public function create(Query $query)
    {
        $this->type->create($this, $query);
    }
}
