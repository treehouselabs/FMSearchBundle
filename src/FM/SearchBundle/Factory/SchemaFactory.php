<?php

namespace FM\SearchBundle\Factory;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;

use FM\SearchBundle\Mapping\Annotation;
use FM\SearchBundle\Factory\Driver\DriverInterface;
use FM\SearchBundle\Factory\SchemaBuilderPass;

class SchemaFactory
{
    public static $namingStrategies = array(
        'underscore' => '\FM\SearchBundle\Mapping\Strategy\UnderscoreNamingStrategy',
    );

    private $driver;
    private $builder;
    private $cacheDir;
    private $debug;

    private $builderPasses = array();
    private $schemas;
    private $classes;

    public function __construct(DriverInterface $driver, SchemaBuilder $builder, $cacheDir, $debug = false)
    {
        $this->driver = $driver;
        $this->builder = $builder;
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
    }

    public function addSchemaBuilderPass(SchemaBuilderPass $pass, $schema)
    {
        if (!array_key_exists($schema, $this->builderPasses)) {
            $this->builderPasses[$schema] = array();
        }

        $this->builderPasses[$schema][] = $pass;
    }

    /**
     * Accepts both a key defined in $namingStrategies, or a FQCN
     */
    protected function getNamingStrategy($strategy)
    {
        $strategyClass = $strategy;

        if (array_key_exists($strategy, static::$namingStrategies)) {
            $strategyClass = static::$namingStrategies[$strategy];
        }

        if (!class_exists($strategyClass)) {
            throw new \LogicException(sprintf('Naming strategy class "%s" does not exist', $strategyClass));
        }

        return new $strategyClass();
    }

    protected function loadSchemas()
    {
        // use cached schemas
        $cache = new ConfigCache($this->cacheDir . '/schemas.php', $this->debug);

        if (!$cache->isFresh()) {

            $this->schemas = array();
            $this->classes = array();

            // collect resources
            $resources = array();

            foreach ($this->driver->getAllClassNames() as $class) {
                $reflClass = new \ReflectionClass($class);

                // add class to resources
                $resources[] = new FileResource($reflClass->getFilename());

                if ($annotation = $this->builder->getSchemaAnnotation($reflClass)) {

                    $strategy = $this->getNamingStrategy($annotation->get('namingStrategy', 'underscore'));
                    $repositoryClass = $annotation->get('repositoryClass', 'FM\SearchBundle\Repository\DocumentRepository');

                    $name = $annotation->get('name', $reflClass->getShortName());
                    $name = $strategy->classToSchemaName($name);

                    $schema = $this->builder->buildSchema($name, $reflClass, $strategy);
                    $schema->setRepositoryClass($repositoryClass);
                    $schema->setEntityClass($class);

                    if (array_key_exists($name, $this->builderPasses)) {
                        foreach ($this->builderPasses[$name] as $pass) {
                            $pass->build($schema, $this->builder);

                            $reflPass = new \ReflectionClass($pass);
                            $resources[] = new FileResource($reflPass->getFilename());
                        }
                    }

                    $this->builder->validateSchema($schema);

                    $this->schemas[$name] = $schema;
                    $this->classes[$reflClass->name] = $name;
                }
            }

            $serializedSchemas = $this->builder->serializeSchemas($this->schemas);

            $content = sprintf(<<<EOF
<?php
    \$schemas = %s;
    \$classes = %s;

    return array(
        \$schemas,
        \$classes
    );

EOF
                ,
                var_export($serializedSchemas, true),
                var_export(serialize($this->classes), true)
            );

            $cache->write($content, $resources);

        } else {
            list($schemas, $classes) = include $cache;

            $this->schemas = $this->builder->unserializeSchemas($schemas);
            $this->classes = unserialize($classes);
        }
    }

    public function getSchema($name)
    {
        if (is_null($this->schemas)) {
            $this->loadSchemas();
        }

        if (!array_key_exists($name, $this->schemas)) {
            throw new \OutOfBoundsException(sprintf('There is no schema "%s" defined, available schema\'s are: %s', $name, implode(', ', array_keys($this->schemas))));
        }

        return $this->schemas[$name];
    }

    public function getSchemaName($class)
    {
        if (is_null($this->classes)) {
            $this->loadSchemas();
        }

        if (!array_key_exists($class, $this->classes)) {
            throw new \OutOfBoundsException(sprintf('There is no schema for class "%s" defined', $class));
        }

        return $this->classes[$class];
    }
}
