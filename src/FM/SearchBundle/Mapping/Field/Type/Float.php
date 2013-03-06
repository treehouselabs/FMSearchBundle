<?php

namespace FM\SearchBundle\Mapping\Field\Type;

use FM\SearchBundle\Mapping\Field\Type;

class Float implements Type
{
    public function convertToPhpValue($value)
    {
        return (null === $value) ? null : (float) $value;
    }

    public function convertToSolrValue($value)
    {
        return (null === $value) ? null : (float) $value;
    }
}
