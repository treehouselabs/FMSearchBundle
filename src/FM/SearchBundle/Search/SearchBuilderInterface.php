<?php

namespace FM\SearchBundle\Search;

interface SearchBuilderInterface
{
    public function setQuery($query);
    public function addFilter($name, $type = null, array $options = array());
    public function getFilters();
    public function getSearch();
}
