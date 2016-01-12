<?php

namespace FM\SearchBundle\Mapping\Field\Type;

use FM\SearchBundle\Mapping\Field\Type;

class IntegerType implements Type
{
    public function convertToPhpValue($value)
    {
        return (null === $value) ? null : (int) $value;
    }

    public function convertToSolrValue($value)
    {
        return (null === $value) ? null : (int) $value;
    }
}
