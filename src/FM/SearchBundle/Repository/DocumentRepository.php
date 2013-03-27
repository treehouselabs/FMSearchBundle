<?php

namespace FM\SearchBundle\Repository;

use Solarium\QueryType\Select\Query\Query;

use FM\SearchBundle\Mapping\Schema;
use FM\SearchBundle\DocumentManager;

class DocumentRepository
{
    protected $manager;
    protected $schema;

    public function __construct(DocumentManager $manager, Schema $schema)
    {
        $this->manager = $manager;
        $this->schema = $schema;
    }

    public function createQuery()
    {
        return $this->manager->getClient()->createSelect();
    }

    public function query(Query $query)
    {
        $endpoint = $this->manager->getEndpoint($this->schema);
        $result = $this->manager->getClient()->select($query, $endpoint);

        return $result->getDocuments();
    }
}
