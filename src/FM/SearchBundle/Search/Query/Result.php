<?php

namespace FM\SearchBundle\Search\Query;

use Solarium\QueryType\Select\Result\Result as BaseResult;

use FM\SearchBundle\Search\Hydration\Hydrator;

use IteratorAggregate;
use Countable;

/**
 * Wrapper class around Solarium resultset.
 */
class Result implements IteratorAggregate, Countable
{
    private $result;
    private $hydrator;
    private $documents;

    public function __construct(BaseResult $result, Hydrator $hydrator)
    {
        $this->result = $result;
        $this->hydrator = $hydrator;
    }

    /**
     * Returns the number of results for this search.
     *
     * @return integer
     */
    public function count()
    {
        return $this->result->count();
    }

    /**
     * Returns the total number of results (ie: for all pages).
     *
     * @return integer
     */
    public function total()
    {
        return $this->result->getNumFound();
    }

    /**
     * Hydrates documents and returns the result.
     *
     * @return array
     */
    public function getDocuments()
    {
        if (!$this->documents) {
            $this->documents = $this->hydrator->hydrateAll($this->result->getDocuments());
        }

        return $this->documents;
    }

    /**
     * IteratorAggregate implementation
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getDocuments());
    }

    /**
     * Gets facets for the search.
     *
     * @return Solarium\QueryType\Select\Result\FacetSet
     */
    public function getFacets()
    {
        return $this->result->getFacetSet();
    }
}
