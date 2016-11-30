<?php

namespace FM\SearchBundle\Search\Hydration;

use FM\SearchBundle\DocumentManager;
use FM\SearchBundle\Event\HydrateEvent;
use FM\SearchBundle\Event\SearchEvents;

abstract class AbstractHydrator implements Hydrator
{
    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * Initializes a new instance of a class derived from `AbstractHydrator`.
     *
     * @param \FM\SearchBundle\DocumentManager $dm The DocumentManager to use
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @param array $documents
     *
     * @return array
     */
    public function hydrateAll(array $documents)
    {
        $hydrated = [];

        foreach ($documents as $key => $document) {
            try {

                // hydrate document
                $hydratedDocument = $this->hydrate($document);

                // event hook to modify hydrated document
                $this->dm->dispatchEvent(SearchEvents::HYDRATE, new HydrateEvent($hydratedDocument));

                // add to hydrated documents
                $hydrated[$key] = $hydratedDocument;
            } catch (HydrationException $e) {
                // something went wrong, don't add it
            }
        }

        return $hydrated;
    }
}
