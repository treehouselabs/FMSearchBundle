<?php

namespace FM\SearchBundle\Mapping\Filter;

use FM\SearchBundle\Mapping\Filter;

interface Type
{
    const EQUALS = 'equals';
    const RANGE  = 'range';

    public function isMultiValued();
    public function getQuery($name);

    /**
     * Normalizes choice to use in Solr query.
     *
     * @param  Filter $filter
     * @param  mixed  $choice
     * @return mixed
     */
    public function normalizeChoice(Filter $filter, $choice);
}
