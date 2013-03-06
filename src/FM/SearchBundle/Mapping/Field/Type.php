<?php

namespace FM\SearchBundle\Mapping\Field;

interface Type
{
    const STRING   = 'string';
    const TEXT     = 'text';
    const BOOLEAN  = 'boolean';
    const INTEGER  = 'integer';
    const FLOAT    = 'float';
    const DATETIME = 'datetime';
    const LOCATION = 'location';

    public function convertToPhpValue($value);
    public function convertToSolrValue($value);
}
