<?php

namespace FM\SearchBundle\PropertyAccess;

use Symfony\Component\PropertyAccess\PropertyAccessor;

class Uuid extends PropertyAccessor
{
    public function getValue($property, $path)
    {
        $value = parent::getValue($property, $path);

        return sha1(sprintf('%s:%s', get_class($property), $value));
    }
}
