<?php

namespace FM\SearchBundle\Search\Hydration;

use FM\SearchBundle\DocumentManager;

interface Hydrator
{
    public function __construct(DocumentManager $dm);
    public function hydrateAll(array $documents);
    public function hydrate(\ArrayAccess $document);
}
