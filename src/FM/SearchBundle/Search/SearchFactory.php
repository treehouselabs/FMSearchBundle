<?php

namespace FM\SearchBundle\Search;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\OptionsResolver\OptionsResolver;
use FM\SearchBundle\Mapping\Registry;
use FM\SearchBundle\Mapping\Schema;

class SearchFactory
{
    private $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function create(AbstractType $type, Schema $schema, array $options = array())
    {
        // resolve options
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->setOptional($resolver);
        $type->configureOptions($resolver);
        $options = $resolver->resolve($options);

        // create new builder and build the search
        $builder = $this->createBuilder($this->registry, $schema, $options);
        $type->buildSearch($builder, $options);

        return $builder->getSearch();
    }

    protected function createBuilder(Registry $registry, Schema $schema, array $options)
    {
        return new SearchBuilder($registry, $schema, $options, new EventDispatcher());
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
    }

    protected function setOptional(OptionsResolver $resolver)
    {
        $resolver->setDefined(array(
            'field',
            'values',
        ));
    }
}
