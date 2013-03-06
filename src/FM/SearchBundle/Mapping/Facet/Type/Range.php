<?php

namespace FM\SearchBundle\Mapping\Facet\Type;

use Solarium\QueryType\Select\Query\Query;

use FM\SearchBundle\Mapping\Facet;
use FM\SearchBundle\Mapping\Facet\Type;

class Range implements Type
{
    public function create(Facet $facet, Query $query)
    {
        $query->getFacetSet()->createFacetRange($facet->getName(), $facet->getConfig());
    }
}
