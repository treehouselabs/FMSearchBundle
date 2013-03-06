<?php

namespace FM\SearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class HydrateEvent extends Event
{
    private $document;

    /**
     * @param mixed $document Whatever the hydrator hydrates to, eg: an array or object
     */
    public function __construct(&$document)
    {
        $this->document =& $document;
    }

    public function &getDocument()
    {
        return $this->document;
    }
}
