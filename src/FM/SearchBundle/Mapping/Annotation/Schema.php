<?php

namespace FM\SearchBundle\Mapping\Annotation;

/**
 * @Annotation
 */
class Schema extends Annotation
{
    public function getName()
    {
        return $this->get('name');
    }
}
