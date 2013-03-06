<?php

namespace FM\SearchBundle\Mapping\Field\Type;

use FM\SearchBundle\Mapping\Field\Type;

class Location implements Type
{
    public function convertToPhpValue($value)
    {
        return (null === $value) ? null : explode(',', $value);
    }

    public function convertToSolrValue($value)
    {
        return (null === $value) ? null : implode(',', $value);
    }
}
