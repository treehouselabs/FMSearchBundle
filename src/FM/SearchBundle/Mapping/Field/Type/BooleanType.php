<?php

namespace FM\SearchBundle\Mapping\Field\Type;

use FM\SearchBundle\Mapping\Field\Type;

class BooleanType implements Type
{
    public function convertToPhpValue($value)
    {
        if (is_null($value)) {
            return null;
        }

        return ($value === 'true');
    }

    public function convertToSolrValue($value)
    {
        if (is_null($value)) {
            return null;
        }

        return $value ? 'true' : 'false';
    }
}
