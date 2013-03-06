<?php

namespace FM\SearchBundle\Search;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

interface SearchTypeInterface
{
    public function buildSearch(SearchBuilderInterface $builder, array $options);
    public function setDefaultOptions(OptionsResolverInterface $resolver);
}
