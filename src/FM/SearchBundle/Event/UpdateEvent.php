<?php

namespace FM\SearchBundle\Event;

use FM\SearchBundle\DocumentManager;
use Symfony\Component\EventDispatcher\Event;

use FM\SearchBundle\Mapping\Document;

class UpdateEvent extends Event
{
    private $document;
    private $dm;

    public function __construct(Document $document, DocumentManager $dm)
    {
        $this->document = $document;
        $this->dm = $dm;
    }

    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @return \FM\SearchBundle\DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->dm;
    }
}
