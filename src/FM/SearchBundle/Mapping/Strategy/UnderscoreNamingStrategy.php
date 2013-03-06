<?php

namespace FM\SearchBundle\Mapping\Strategy;

/**
 * Naming strategy implementing the underscore naming convention.
 * Converts 'MyEntity' to 'my_entity', which is the default for Solr.
 */
class UnderscoreNamingStrategy implements NamingStrategy
{
    /**
     * {@inheritdoc}
     */
    public function classToSchemaName($className)
    {
        if (strpos($className, '\\') !== false) {
            $className = substr($className, strrpos($className, '\\') + 1);
        }

        return $this->underscore($className);
    }

    /**
     * {@inheritdoc}
     */
    public function propertyToFieldName($propertyName, $className = null)
    {
        return $this->underscore($propertyName);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function underscore($string)
    {
        $string = preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $string);

        return strtolower($string);
    }
}
