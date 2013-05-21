<?php

namespace FM\SearchBundle\Factory;

use Doctrine\Common\Annotations\Reader;

use FM\SearchBundle\Mapping\Annotation;
use FM\SearchBundle\Mapping\Field;
use FM\SearchBundle\Mapping\Registry;
use FM\SearchBundle\Mapping\Accessor\Type as AccessorType;
use FM\SearchBundle\Mapping\Schema;
use FM\SearchBundle\Mapping\Strategy\NamingStrategy;

use ReflectionClass;

class SchemaBuilder
{
    private $annotationReader;
    private $registry;

    public function __construct(Reader $reader, Registry $registry)
    {
        $this->annotationReader = $reader;
        $this->registry = $registry;
    }

    /**
     * Returns the @Schema annotation of a class, if defined.
     *
     * @param  ReflectionClass       $reflClass
     * @return Annotation\Annotation
     */
    public function getSchemaAnnotation(ReflectionClass $reflClass)
    {
        foreach ($this->annotationReader->getClassAnnotations($reflClass) as $annotation) {
            if ($annotation instanceof Annotation\Schema) {
                return $annotation;
            }
        }
    }

    /**
     * Builds a new Schema instance, based on a class' annotations.
     *
     * @param  string          $name     The schema name
     * @param  ReflectionClass $class    The class
     * @param  NamingStrategy  $strategy Naming strategy to use
     * @return Schema
     */
    public function buildSchema($name, ReflectionClass $class, NamingStrategy $strategy)
    {
        $className = $class->name;

        $schema = new Schema($name);

        if ($annotation = $this->getSchemaAnnotation($class)) {
            if ($client = $annotation->get('client')) {
                $schema->setClient($client);
            }

            if ($uniqueKey = $annotation->get('uniqueKey')) {
                $schema->setUniqueKey($uniqueKey);
            }
        }

        foreach ($class->getProperties() as $property) {
            if ($property->getDeclaringClass()->name === $className) {
                foreach ($this->annotationReader->getPropertyAnnotations($property) as $annotation) {
                    if ($annotation instanceof Annotation\Field) {
                        $name = $strategy->propertyToFieldName($annotation->get('name', $property->getName()));
                        $path = $annotation->get('path', $property->getName());
                        $this->addField($schema, $name, $path, $annotation);
                    }
                    if ($annotation instanceof Annotation\UniqueKey) {
                        $name = $strategy->propertyToFieldName($annotation->get('name', $property->getName()));
                        $schema->setUniqueKey($name);
                    }
                }
            }
        }

        return $schema;
    }

    /**
     * Validates a schema.
     *
     * @param  Schema $schema
     * @throws \LogicException When schema is invalid
     */
    public function validateSchema(Schema $schema)
    {
        if (!$schema->getUniqueKey()) {
            throw new \LogicException(
                'No unique key defined on schema, please set a "uniqueKey"
                attribute on the @Schema annotation, or annotate a property
                with the @UniqueKey class'
            );
        }

        if (!$schema->hasField($schema->getUniqueKey())) {
            throw new \LogicException(sprintf(
                'Schema does not contain a field named "%s"',
                $schema->getUniqueKey()
            ));
        }
    }

    /**
     * Creates a new field.
     *
     * @param  string $type         The field type
     * @param  string $name         The field name
     * @param  string $accessorType The accessor key, as defined in the registry
     * @param  string $propertyName Optional property name which the field is
     *                              mapped to
     * @return Field
     */
    public function createField($type, $name, $accessorType = null, $propertyName = null)
    {
        $fieldType = $this->registry->getFieldType($type);

        $accessorType = $accessorType ?: AccessorType::GRAPH;
        $accessor = $this->registry->getAccessorType($accessorType);

        return new Field($fieldType, $name, $accessor, $propertyName);
    }

    /**
     * Adds a field to the schema.
     *
     * @param Schema                $schema
     * @param string                $name
     * @param string                $propertyName
     * @param Annotation\Annotation $annotation
     */
    protected function addField(Schema $schema, $name, $propertyName, $annotation)
    {
        $field = $this->createField(
            $annotation->getType(),
            $name,
            $annotation->get('accessor'),
            $propertyName
        );

        if ($annotation->has('required')) {
            $field->setRequired($annotation->getBool('required'));
        }

        if ($annotation->has('multiValued')) {
            $field->setMultiValued($annotation->getBool('multiValued'));
        }

        $schema->addField($field);
    }

    /**
     * Serializes schemas. See serializeSchema for the implementation.
     *
     * @param  array  $schemas
     * @return array           An array with the serialized schemas
     */
    public function serializeSchemas(array $schemas)
    {
        $serialized = array();

        foreach ($schemas as $name => $schema) {
            $serialized[$name] = $this->serializeSchema($schema);
        }

        return $serialized;
    }

    /**
     * Serializes a schema. Uses PHP's internal `serialize` method for this,
     * with the exception of the fields accessors. Since these are often classes
     * with closures or services, only the registered accessor type is
     * serialized. When unserializing, the accessor type is looked up in the
     * registry and set into the field again.
     *
     * @param  Schema $schema
     * @return string
     */
    public function serializeSchema(Schema $schema)
    {
        foreach ($schema->getFields() as $field) {
            if (null === $accessorType = $this->registry->getTypeName('accessor', $field->getAccessor())) {
                throw new \LogicException(sprintf('Could not determine type for accessor "%s". Did you forget to register it?', get_class($field->getAccessor())));
            }
            $field->setAccessorType($accessorType);
        }

        return serialize($schema);
    }

    /**
     * Unserializes an array of schemas.
     *
     * @param  array  $schemas
     * @return array           An array with the unserialized schemas
     */
    public function unserializeSchemas(array $schemas)
    {
        $unserialized = array();

        foreach ($schemas as $name => $schema) {
            $unserialized[$name] = $this->unserializeSchema(unserialize($schema));
        }

        return $unserialized;
    }

    /**
     * Unserializes a schema. Resets the accessors of the fields. See
     * serializeSchema.
     *
     * @param  Schema $schema
     * @return Schema
     */
    public function unserializeSchema(Schema $schema)
    {
        foreach ($schema->getFields() as $field) {
            $accessor = $this->registry->getAccessorType($field->getAccessorType());
            $field->setAccessor($accessor);
        }

        return $schema;
    }
}
