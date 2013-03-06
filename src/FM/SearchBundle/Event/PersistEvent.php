<?php

namespace FM\SearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use FM\SearchBundle\Mapping\Document;

class PersistEvent extends Event
{
    private $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function getDocument()
    {
        return $this->document;
    }
}
