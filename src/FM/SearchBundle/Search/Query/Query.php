<?php

namespace FM\SearchBundle\Search\Query;

use Solarium\Client;

use FM\SearchBundle\DocumentManager;
use FM\SearchBundle\Mapping\Filter;
use FM\SearchBundle\Search\Search;

/**
 * Wrapper class around a Solarium query.
 *
 * Usage:
 *
 * <code>
 *   $query = new Query($dm, $search);
 *   $query->bind(array('filter1' => 'filter_value'));
 *   $result = $query->getResult();
 * </code>
 */
class Query
{
    const HYDRATE_ARRAY = 'array';

    private $manager;
    private $search;
    private $values;
    private $query;

    private $result;

    private $prepared = false;
    private $bound = false;

    public function __construct(DocumentManager $manager, Search $search)
    {
        $this->manager = $manager;
        $this->search = $search;
    }

    public function getQuery()
    {
        if (is_null($this->query)) {
            $this->query = $this->manager->getClient()->createSelect();
        }

        return $this->query;
    }

    /**
     * Returns whether value for a given filter is valid. At the moment this is
     * the same for all filters. But the filter is passed along, should you want
     * to change this on a per-filter base.
     *
     * @param  Filter  $filter
     * @param  mixed   $value
     * @return boolean
     */
    protected function isValidValue(Filter $filter, $value)
    {
        return $filter->isValidValue($value);
    }

    /**
     * Sets filter queries based on bound values, and creates facets if
     * configured.
     */
    protected function prepare()
    {
        if ($this->prepared) {
            return;
        }

        if ($query = $this->search->getQuery()) {
            $this->getQuery()->setQuery($query);
        }

        foreach ($this->search->getFilters() as $name => $filter) {

            // create filter query and apply value
            if (isset($this->values[$name])) {
                $fq = $this->getQuery()->createFilterQuery($name);

                try {
                    $filter->setQuery($fq, $this->values[$name]);
                } catch (\OutOfBoundsException $e) {
                    // Most common case is: someone supplied a non-existing
                    // option. That's ok though, we'll let those slide.
                }
            }

            // create facet
            if ($facet = $filter->getFacet()) {
                $facet->create($this->getQuery());
            }
        }

        $this->prepared = true;
    }

    /**
     * Binds values to this query. When preparing, the values will be applied
     * to the configured filters.
     *
     * @param  array          $values
     * @throws LogicException When query is already bound
     */
    public function bind(array $values)
    {
        if ($this->bound) {
            throw new \LogicException('Query is already bound');
        }

        $this->values = array();

        foreach ($values as $name => $value) {
            if ($this->search->hasFilter($name)) {
                $filter = $this->search->getFilter($name);
                if ($this->isValidValue($filter, $value)) {
                    $this->values[$name] = $value;
                }
            }
        }

        $this->bound = true;
    }

    /**
     * Executes the search and returns the result.
     *
     * @param  string $hydrationMode The hydration mode
     * @return Result
     */
    public function getResult($hydrationMode = self::HYDRATE_ARRAY)
    {
        if (!$this->bound) {
            throw new \LogicException('You must bind the query before getting the results');
        }

        $this->prepare();

        $client = $this->manager->getClient();

        $endpoint = $this->manager->getEndpoint($this->search->getSchema());
        $hydrator = $this->manager->getHydrator($hydrationMode);

        $result = $client->select($this->getQuery(), $endpoint);

        $this->result = new Result($result, $hydrator);

        return $this->result;
    }
}
