<?php

namespace FM\SearchBundle\Search\Hydration;

class ArrayHydrator extends AbstractHydrator
{
    /**
     * @todo The field values need to be converted to PHP values according to
     *       their respective types. The ResponseParser needs to be extended
     *       for this, and provided the schema that is used.
     */
    public function hydrate(\ArrayAccess $document)
    {
        return $document->getFields();
    }
}
