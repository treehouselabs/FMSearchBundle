<?php

namespace FM\SearchBundle\Mapping\Facet\Type;

use Solarium\QueryType\Select\Query\Query;

use FM\SearchBundle\Mapping\Facet;
use FM\SearchBundle\Mapping\Facet\Type;

class RangeType implements Type
{
    public function create(Facet $facet, Query $query)
    {
        $query->getFacetSet()->createFacetRange(array_merge(
            array('key' => $facet->getName()),
            $facet->getConfig()->all()
        ));
    }
}
