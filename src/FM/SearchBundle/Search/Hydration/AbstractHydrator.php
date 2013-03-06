<?php

namespace FM\SearchBundle\Search\Hydration;

use FM\SearchBundle\DocumentManager;
use FM\SearchBundle\Event\SearchEvents;
use FM\SearchBundle\Event\HydrateEvent;

abstract class AbstractHydrator implements Hydrator
{
    /**
     * @var \FM\SearchBundle\DocumentManager
     */
    protected $dm;

    /**
     * Initializes a new instance of a class derived from <tt>AbstractHydrator</tt>.
     *
     * @param \FM\SearchBundle\DocumentManager $dm The DocumentManager to use.
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function hydrateAll(array $documents)
    {
        $hydrated = array();

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
