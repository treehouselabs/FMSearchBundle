<?php

namespace FM\SearchBundle\Event;

use FM\SearchBundle\DocumentManager;
use Symfony\Component\EventDispatcher\Event;

class CommitEvent extends Event
{
    private $schemas;
    private $dm;

    public function __construct(array $schemas, DocumentManager $dm)
    {
        $this->schemas = $schemas;
        $this->dm = $dm;
    }

    /**
     * @return array
     */
    public function getSchemas()
    {
        return $this->schemas;
    }

    /**
     * @return \FM\SearchBundle\DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->dm;
    }
}
