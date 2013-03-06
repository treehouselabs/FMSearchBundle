<?php

namespace FM\SearchBundle\Mapping\Strategy;

/**
 * @see http://docs.doctrine-project.org/en/latest/reference/namingstrategy.html
 */
interface NamingStrategy
{
    public function classToSchemaName($className);
    public function propertyToFieldName($propertyName, $className = null);
}
