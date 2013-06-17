<?php

namespace FM\SearchBundle\Repository;

use Solarium\QueryType\Select\Query\Query;

use FM\SearchBundle\Mapping\Schema;
use FM\SearchBundle\DocumentManager;
use FM\SearchBundle\Search\SearchTypeInterface;

class DocumentRepository
{
    protected $manager;
    protected $schema;

    public function __construct(DocumentManager $manager, Schema $schema)
    {
        $this->manager = $manager;
        $this->schema = $schema;
    }

    public function createQuery(SearchTypeInterface $type)
    {
        $search = $this->manager->getSearchFactory()->create($type, $this->schema, array());

        return $this->manager->createQuery($search);
    }

    public function query(Query $query)
    {
        $endpoint = $this->manager->getEndpoint($this->schema);
        $result = $this->manager->getClient()->select($query, $endpoint);

        return $result->getDocuments();
    }
}
