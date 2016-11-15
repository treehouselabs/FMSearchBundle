<?php

namespace FM\SearchBundle\Search;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface SearchTypeInterface
{
    public function buildSearch(SearchBuilderInterface $builder, array $options);
    public function configureOptions(OptionsResolver $resolver);
}
