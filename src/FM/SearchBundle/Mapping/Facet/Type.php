<?php

namespace FM\SearchBundle\Mapping\Facet;

use Solarium\QueryType\Select\Query\Query;

use FM\SearchBundle\Mapping\Facet;

interface Type
{
    const FIELD = 'field';
    const RANGE = 'range';

    public function create(Facet $facet, Query $query);
}
