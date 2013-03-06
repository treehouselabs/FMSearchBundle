<?php

namespace FM\SearchBundle\Mapping\Filter\Type;

use FM\SearchBundle\Mapping\Filter;
use FM\SearchBundle\Mapping\Filter\Type;

class Equals implements Type
{
    public function isMultiValued()
    {
        return false;
    }

    public function getChoice(Filter $filter, $choice)
    {
        $choices = $filter->getChoices();

        if (isset($choices[$choice])) {
            return $choices[$choice];
        }
    }

    public function getQuery($name)
    {
        return $name . ':%1%';
    }

    public function normalizeChoice(Filter $filter, $choice)
    {
        $filterChoices = $filter->getChoices();

        if (!isset($filterChoices[$choice])) {
            throw new \OutOfBoundsException(sprintf(
                '"%s" is not a valid choice',
                $choice
            ));
        }

        return $filterChoices[$choice];
    }
}
