<?php

namespace FM\SearchBundle\Mapping\Annotation;

/**
 * @Annotation
 */
class Field extends Annotation
{
    public function validate()
    {
        if (!$this->has('type')) {
            throw new \InvalidArgumentException(sprintf('You must define a type for field "%s"', $this->get('name')));
        }
    }

    public function getName()
    {
        return $this->get('name');
    }

    public function getType()
    {
        return $this->get('type');
    }

    public function getAccessor()
    {
        return $this->get('accessor');
    }

    public function getBoost()
    {
        return $this->get('boost');
    }
}
