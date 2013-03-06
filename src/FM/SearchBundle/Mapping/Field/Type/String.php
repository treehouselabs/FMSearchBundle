<?php

namespace FM\SearchBundle\Mapping\Field\Type;

use FM\SearchBundle\Mapping\Field\Type;

class String implements Type
{
    public function convertToPhpValue($value)
    {
        return (is_resource($value)) ? stream_get_contents($value) : $value;
    }

    public function convertToSolrValue($value)
    {
        return $value;
    }
}
