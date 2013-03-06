<?php

namespace FM\SearchBundle\Mapping;

use Solarium\QueryType\Select\Result\AbstractDocument;

/**
 * Wrapper class that contains both the schema and the actual document used by Solarium.
 */
class Document
{
    private $schema;
    private $document;

    /**
     * @param Schema           $schema
     * @param AbstractDocument $document
     */
    public function __construct(Schema $schema, AbstractDocument $document)
    {
        $this->schema = $schema;
        $this->document = $document;
    }

    /**
     * @return Schema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @return AbstractDocument
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Forwards all calls to the inner Document object
     */
    public function __call($name, $args)
    {
        if (method_exists($this->document, $name)) {
            return call_user_func_array(array($this->document, $name), $args);
        }
    }

    /**
     * @return The unique key value if set, an object hash otherwise.
     */
    public function __toString()
    {
        $uniqueKey = $this->schema->getUniqueKeyField()->getName();

        if (isset($this->document[$uniqueKey])) {
            return (string) $this->document[$uniqueKey];
        }

        return __CLASS__ . '@' . spl_object_hash($this);
    }
}
