<?php

namespace FM\SearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use FM\SearchBundle\Mapping\Document;
use FM\SearchBundle\Mapping\Schema;

class SetFieldsEvent extends Event
{
    private $schema;
    private $document;
    private $entity;

    public function __construct(Schema $schema, Document $document, $entity)
    {
        $this->schema = $schema;
        $this->document = $document;
        $this->entity = $entity;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function getDocument()
    {
        return $this->document;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}
