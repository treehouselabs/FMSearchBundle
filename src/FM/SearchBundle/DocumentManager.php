<?php

namespace FM\SearchBundle;

use FM\SearchBundle\Event\CommitEvent;
use FM\SearchBundle\Event\UpdateEvent;
use FM\SearchBundle\Mapping\Field\Type\LocationType;
use FM\SearchBundle\Repository\DocumentRepository;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Solarium\Client;
use FM\SearchBundle\Event\SearchEvents;
use FM\SearchBundle\Event\PersistEvent;
use FM\SearchBundle\Event\SetFieldsEvent;
use FM\SearchBundle\Factory\SchemaFactory;
use FM\SearchBundle\Mapping\Field;
use FM\SearchBundle\Mapping\Document;
use FM\SearchBundle\Mapping\Schema;
use FM\SearchBundle\Search\Search;
use FM\SearchBundle\Search\SearchFactory;
use FM\SearchBundle\Search\SearchTypeInterface;
use FM\SearchBundle\Search\Query\Query;
use FM\SearchBundle\Search\Hydration\Hydrator;

class DocumentManager
{
    private $client;
    private $schemaFactory;
    private $searchFactory;
    private $logger;
    private $eventDispatcher;

    private $schemas = array();
    private $schemaClasses = array();
    private $repositories = array();
    private $updates = array();
    private $documentMap = array();
    private $schemaMap = array();
    private $dirtyMap = array();

    private $hydrators = array();
    private $hydrationModes = array(
        Query::HYDRATE_ARRAY => '\FM\SearchBundle\Search\Hydration\ArrayHydrator',
    );

    /**
     * Constructor.
     *
     * @param Client          $client
     * @param SchemaFactory   $schemaFactory
     * @param LoggerInterface $logger
     */
    public function __construct(Client $client, SchemaFactory $schemaFactory, SearchFactory $searchFactory, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->schemaFactory = $schemaFactory;
        $this->searchFactory = $searchFactory;
        $this->logger = $logger;
        $this->eventDispatcher = new EventDispatcher();
    }

    /**
     * Returns the Solarium client used to interface with Solr.
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return SearchFactory
     */
    public function getSearchFactory()
    {
        return $this->searchFactory;
    }

    /**
     * Returns the endpoint used by Solarium for the supplied schema. Each
     * schema is mapped to an endpoint.
     *
     * @param mixed $schema A Schema instance or schema name
     *
     * @return string
     */
    public function getEndpoint($schema = null)
    {
        $endpoint = null;

        if (!is_null($schema)) {
            if (!($schema instanceof Schema)) {
                $schema = $this->getSchema($schema);
            }

            $endpoint = $schema->getClient();
        }

        // Get endpoint first from client, to raise exception on non-existing
        // endpoint. Also, this selects the default endpoint if $endpoint is null
        return $this->client->getEndpoint($endpoint)->getKey();
    }

    /**
     * Returns the schema for a given name or class.
     *
     * @param string $schema The schema name, or the class this schema is mapped to.
     *
     * @return Schema
     */
    public function getSchema($schema)
    {
        if (strpos($schema, '\\') !== false) {
            if (!array_key_exists($schema, $this->schemaClasses)) {
                $this->schemaClasses[$schema] = $this->schemaFactory->getSchemaName($schema);
            }

            $schema = $this->schemaClasses[$schema];
        }

        if (!array_key_exists($schema, $this->schemas)) {
            $this->schemas[$schema] = $this->schemaFactory->getSchema($schema);
        }

        return $this->schemas[$schema];
    }

    /**
     * Gets the repository for a schema.
     *
     * @param string $schema The schema name, or the class this schema is mapped to.
     *
     * @return DocumentRepository
     */
    public function getRepository($schema)
    {
        if (!($schema instanceof Schema)) {
            $schema = $this->getSchema($schema);
        }

        if (!array_key_exists($schema->getName(), $this->repositories)) {
            $repoClass = $schema->getRepositoryClass();
            $this->repositories[$schema->getName()] = new $repoClass($this, $schema);
        }

        return $this->repositories[$schema->getName()];
    }

    /**
     * Returns an update query to be used by the Solarium client. If no update
     * query exists yet, a new one is created. The update query can be used to
     * issue commands for Solr, such as updating and deleting documents.
     *
     * @param Schema $schema
     *
     * @return \Solarium\QueryType\Update\Query\Query
     */
    protected function getUpdate(Schema $schema)
    {
        $schemaName = $schema->getName();

        if (!isset($this->updates[$schemaName])) {
            $this->updates[$schemaName] = $this->client->createUpdate();
        }

        return $this->updates[$schemaName];
    }

    /**
     * Flushes pending updates for the supplied schema to Solr.
     *
     * @param Schema $schema
     */
    private function executeUpdate(Schema $schema)
    {
        $this->client->update($this->updates[$schema->getName()], $this->getEndpoint($schema));
        unset($this->updates[$schema->getName()]);
    }

    /**
     * Returns the hydrator for give mode.
     *
     * @param string $hydrationMode
     *
     * @return Hydrator
     */
    public function getHydrator($hydrationMode)
    {
        if (!isset($this->hydrators[$hydrationMode])) {
            $this->hydrators[$hydrationMode] = $this->createHydrator($hydrationMode);
        }

        return $this->hydrators[$hydrationMode];
    }

    /**
     * Maps a hydration mode to a hydrator class.
     *
     * @param string $mode
     * @param string $class
     *
     * @throws \LogicException if mode already exists
     */
    public function registerHydrationMode($mode, $class)
    {
        if (array_key_exists($mode, $this->hydrationModes)) {
            throw new \LogicException(sprintf(
                'Hydration mode "%s" already exists',
                $mode
            ));
        }

        $this->hydrationModes[$mode] = $class;
    }

    /**
     * Maps a hydration mode to a hydrator instance.
     *
     * @param Hydrator $hydrator
     * @param string   $mode
     *
     * @throws \LogicException if mode already exists
     */
    public function registerHydrator(Hydrator $hydrator, $mode)
    {
        if (array_key_exists($mode, $this->hydrationModes)) {
            throw new \LogicException(sprintf(
                'Hydration mode "%s" already exists',
                $mode
            ));
        }

        $this->hydrationModes[$mode] = get_class($hydrator);
        $this->hydrators[$mode] = $hydrator;
    }

    /**
     * Create a new instance for the given hydration mode.
     *
     * @param string $hydrationMode
     *
     * @return \FM\SearchBundle\Search\Hydration\Hydrator
     */
    public function createHydrator($hydrationMode)
    {
        if (isset($this->hydrationModes[$hydrationMode])) {
            return new $this->hydrationModes[$hydrationMode]($this);
        }

        throw new \LogicException(sprintf('Invalid hydration mode "%s"', $hydrationMode));
    }

    /**
     * Dispatches event.
     *
     * @param string $type  The event type
     * @param Event  $event The event
     */
    public function dispatchEvent($type, Event $event)
    {
        $this->eventDispatcher->dispatch($type, $event);
    }

    /**
     * Adds listener to the internal event dispatcher.
     *
     * @param string $type     The event type
     * @param mixed  $listener The listener
     */
    public function addEventListener($type, $listener)
    {
        $this->eventDispatcher->addListener($type, $listener);
    }

    /**
     * Checks if given class or instance is supported by the document manager.
     *
     * @param mixed $class
     *
     * @return bool
     */
    public function supports($class)
    {
        try {
            if (is_object($class)) {
                $class = get_class($class);
            }
            $this->getSchema($class);

            return true;
        } catch (\OutOfBoundsException $e) {
        }

        return false;
    }

    /**
     * @param Document $document
     *
     * @return bool
     */
    public function isManaged(Document $document)
    {
        return array_key_exists(spl_object_hash($document), $this->documentMap);
    }

    /**
     * Makes a document managed by the DocumentManager. Persisted documents are
     * automatically committed, but only once. If you change the document after
     * calling commit(), you have to persist it again. This is different from
     * how Doctrine's EntityManager works.
     *
     * @param Document $document
     */
    public function persist(Document $document)
    {
        $event = new PersistEvent($document);
        $this->eventDispatcher->dispatch(SearchEvents::PRE_PERSIST, $event);

        $hash = spl_object_hash($document);
        $schema = $document->getSchema()->getName();

        // add to document map if we're not managing it
        if (!array_key_exists($hash, $this->documentMap)) {
            $this->documentMap[$hash] = $document;
            $this->schemaMap[$schema][] = $hash;
        }

        // add to dirty map
        $this->dirtyMap[$schema][$hash] = $document;

        $this->eventDispatcher->dispatch(SearchEvents::POST_PERSIST, $event);
    }

    /**
     * Commits scheduled updates to Solr. Dirty documents are added to the
     * internal update query first.
     *
     * @param mixed $schema Optional schema(s) to issue the commit for, defaults
     *                      to all schemas. Supported values are:
     *                      - a Schema instance
     *                      - the schema name as a string
     *                      - an array containing schema names
     */
    public function commit($schema = null)
    {
        if (is_array($schema)) {
            $schemas = $schema;
        } elseif (is_string($schema)) {
            $schemas = array($schema);
        } elseif ($schema instanceof Schema) {
            $schemas = array($schema->getName());
        } else {
            $schemas = array_keys($this->updates);
        }

        // preCommit
        $event = new CommitEvent($schemas, $this);
        $this->eventDispatcher->dispatch(SearchEvents::PRE_COMMIT, $event);

        foreach ($schemas as $schema) {
            if (!($schema instanceof Schema)) {
                $schema = $this->getSchema($schema);
            }

            $documents = array();

            $schemaName = $schema->getName();

            // add dirty documents first
            if (!empty($this->dirtyMap[$schemaName])) {
                $update = $this->getUpdate($schema);

                $documents = array_values($this->dirtyMap[$schemaName]);

                foreach ($documents as $document) {
                    $event = new UpdateEvent($document, $this);
                    $this->eventDispatcher->dispatch(SearchEvents::PRE_UPDATE, $event);
                }

                $update->addDocuments($documents);
            }

            if (array_key_exists($schemaName, $this->updates)) {
                $this->updates[$schemaName]->addCommit();
                $this->executeUpdate($schema);

                foreach ($documents as $document) {
                    $event = new UpdateEvent($document, $this);
                    $this->eventDispatcher->dispatch(SearchEvents::POST_UPDATE, $event);
                }
            }

            unset($this->dirtyMap[$schemaName]);
        }

        $event = new CommitEvent($schemas, $this);
        $this->eventDispatcher->dispatch(SearchEvents::POST_COMMIT, $event);
    }

    /**
     * Removes an entity from the index. Changes are only committed if you set
     * $andCommit to true, otherwise you have to call commit() manually.
     *
     * @param object $entity    The entity to remove.
     * @param bool   $andCommit Issue commit right now
     */
    public function remove($entity, $andCommit = false)
    {
        $reflClass = new \ReflectionClass($entity);

        $schema = $this->getSchema($reflClass->name);
        $uniqueKey = $schema->getUniqueKeyField()->getValue($entity);

        $this->removeById($schema, $uniqueKey, $andCommit);
    }

    /**
     * Removes a document by its id. Changes are only committed if you set
     * $andCommit to true, otherwise you have to call commit() manually.
     *
     * @param Schema|string $schema    The schema to use
     * @param string        $id        The document's id
     * @param bool          $andCommit Issue commit right now
     */
    public function removeById($schema, $id, $andCommit = false)
    {
        if (!($schema instanceof Schema)) {
            $schema = $this->getSchema($schema);
        }

        $update = $this->getUpdate($schema);
        $update->addDeleteById($id);

        if ($andCommit) {
            $this->commit();
        }

        if ($this->logger) {
            $this->logger->addInfo(sprintf('Removed document "%s"', $id));
        }
    }

    /**
     * Removes one or more documents by a query. Changes are only committed if
     * you set $andCommit to true, otherwise you have to call commit() manually.
     *
     * @param Schema|string $schema    The schema to use
     * @param string        $query     The query
     * @param bool          $andCommit Issue commit right now
     */
    public function removeByQuery($schema, $query, $andCommit = false)
    {
        if (!($schema instanceof Schema)) {
            $schema = $this->getSchema($schema);
        }

        $update = $this->getUpdate($schema);
        $update->addDeleteQuery($query);

        if ($andCommit) {
            $this->commit();
        }
    }

    /**
     * Issues an optimize call to the Solr index.
     *
     * @param mixed $schema Optional schema(s) to issue the commit for, defaults
     *                      to all schemas. Supported values are:
     *                      - a Schema instance
     *                      - the schema name as a string
     *                      - an array containing schema names
     */
    public function optimize($schema = null)
    {
        if (is_array($schema)) {
            $schemas = $schema;
        } elseif (is_string($schema)) {
            $schemas = array($schema);
        } elseif ($schema instanceof Schema) {
            $schemas = array($schema->getName());
        } else {
            $schemas = array_keys($this->updates);
        }

        foreach ($schemas as $schema) {
            if (!($schema instanceof Schema)) {
                $schema = $this->getSchema($schema);
            }

            $update = $this->getUpdate($schema);
            $update->addOptimize();

            $this->executeUpdate($schema);
        }
    }

    /**
     * Creates a new document, based on the given schema.
     *
     * @param Schema $schema
     *
     * @return Document
     */
    public function createDocument(Schema $schema)
    {
        $document = $this->getUpdate($schema)->createDocument();

        return new Document($schema, $document);
    }

    /**
     * Creates a new query, based on a predefined search.
     *
     * @param Search $search
     *
     * @return Query
     */
    public function createQuery(Search $search)
    {
        return new Query($this, $search);
    }

    /**
     * Creates a new search.
     *
     * @param SearchTypeInterface $searchType
     *
     * @return Schema $schema
     */
    public function createSearch(SearchTypeInterface $searchType, Schema $schema)
    {
        return $this->searchFactory->create($searchType, $schema);
    }

    /**
     * Creates a document, populates it with the entity values, and indexes it.
     * Changes are only committed if you set $andCommit to true, otherwise you
     * have to call commit() manually.
     *
     * @param object $entity
     * @param bool   $andCommit Issue commit right now
     */
    public function index($entity, $andCommit = false)
    {
        $reflClass = new \ReflectionClass($entity);
        $className = $reflClass->name;

        $schema = $this->getSchema($className);

        $document = $this->createDocument($schema);

        $this->setFields($schema, $document, $entity);

        $this->persist($document);

        if ($this->logger) {
            $this->logger->addInfo(sprintf('Indexed document "%s"', $document));
        }

        if ($andCommit) {
            $this->commit();
        }
    }

    /**
     * Sets fields defined in the schema to the given document, for the given
     * entity. Empty/null values are skipped.
     *
     * @param Schema   $schema
     * @param Document $document
     * @param object   $entity
     */
    public function setFields(Schema $schema, Document $document, $entity)
    {
        $event = new SetFieldsEvent($schema, $document, $entity);
        $this->eventDispatcher->dispatch(SearchEvents::PRE_SET_FIELDS, $event);

        foreach ($schema->getFields() as $field) {
            $value = $field->getValue($entity);

            if (is_null($value) || (is_string($value) && ($value === ''))) {
                if ($field->isRequired()) {
                    throw new \RuntimeException(
                        sprintf('Empty value for field "%s"', $field->getName())
                    );
                }

                // move along
                continue;
            }

            $this->setFieldValue($document, $field, $value);
        }

        $this->eventDispatcher->dispatch(SearchEvents::POST_SET_FIELDS, $event);
    }

    /**
     * Sets the value for a field to the document.
     *
     * @param Document $document
     * @param Field    $field
     * @param mixed    $value
     *
     * @throws \UnexpectedValueException When the supplied value doesn't match
     *                                   the field's definition.
     */
    public function setFieldValue(Document $document, Field $field, $value)
    {
        if (is_array($value) && !($field->getType() instanceof LocationType)) {
            if (!$field->isMultiValued()) {
                throw new \UnexpectedValueException(sprintf(
                    'Got an array, but field "%s" is not multiValued',
                    $field->getName()
                ));
            }

            foreach ($value as $val) {
                $document->addField(
                    $field->getName(),
                    $field->getType()->convertToSolrValue($val),
                    $field->getBoost()
                );
            }
        } else {
            $document->addField(
                $field->getName(),
                $field->getType()->convertToSolrValue($value),
                $field->getBoost()
            );
        }
    }
}
