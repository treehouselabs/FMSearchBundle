<?php

namespace FM\SearchBundle\Mapping\Filter;

use FM\SearchBundle\Mapping\Filter;

interface Type
{
    const EQUALS = 'equals';
    const RANGE  = 'range';

    /**
     * @return boolean
     */
    public function isMultiValued();

    /**
     * @param  string $name
     * @return string
     */
    public function getQuery($name);

    /**
     * @param  Filter $filter
     * @param  mixed $choice
     * @return mixed
     */
    public function getChoice(Filter $filter, $choice);

    /**
     * Normalizes choice to use in Solr query.
     *
     * @param  Filter $filter
     * @param  mixed  $choice
     * @return mixed
     */
    public function normalizeChoice(Filter $filter, $choice);
}
