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

    public function getSchemaAnnotation(ReflectionClass $reflClass)
    {
        foreach ($this->annotationReader->getClassAnnotations($reflClass) as $annotation) {
            if ($annotation instanceof Annotation\Schema) {
                return $annotation;
            }
        }
    }

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

    public function createField($type, $name, $accessorType = null, $propertyName = null)
    {
        $fieldType = $this->registry->getFieldType($type);

        $accessorType = $accessorType ?: AccessorType::GRAPH;
        $accessor = $this->registry->getAccessorType($accessorType);

        return new Field($fieldType, $name, $accessor, $propertyName);
    }

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
}
