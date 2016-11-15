<?php

namespace FM\SearchBundle\Search;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base class for search types. If you want to create a simple search, you can
 * extend setQuery and setFilters, and you're done.
 */
abstract class AbstractType implements SearchTypeInterface
{
    /**
     * Builds a search using the supplied SearchBuilder.
     * Calls setQuery and setFilters
     *
     * @param SearchBuilderInterface $builder
     */
    public function buildSearch(SearchBuilderInterface $builder, array $options)
    {
        $this->setQuery($builder, $options);
        $this->setFilters($builder, $options);
    }

    /**
     * Sets the main query; this is to limit the set of documents that is used
     * for filtering. Use this to ensure you are only searching in a certain
     * set of documents.
     */
    public function setQuery(SearchBuilderInterface $builder, array $options)
    {
    }

    /**
     * Sets filters to use in the search query.
     */
    public function setFilters(SearchBuilderInterface $builder, array $options)
    {
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }
}
