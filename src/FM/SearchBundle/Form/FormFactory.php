<?php

namespace FM\SearchBundle\Form;

use Symfony\Component\Form\FormFactory as BaseFactory;

use FM\SearchBundle\Form\Type\FilteredSearchType;
use FM\SearchBundle\Search\Search;

class FormFactory
{
    private $factory;

    public function __construct(BaseFactory $factory)
    {
        $this->factory = $factory;
    }

    public function create(Search $search, $type = null, array $options = array())
    {
        if (null === $type) {
            $type = new FilteredSearchType();
        }

        $data = null;
        $options = array_merge(
            $options,
            array(
                'search' => $search
            )
        );

        return $this->factory->createNamed('search', $type, $data, $options);
    }
}
