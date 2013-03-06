<?php

namespace FM\SearchBundle\Factory;

use FM\SearchBundle\Mapping\Schema;

interface SchemaBuilderPass
{
    public function build(Schema $schema, SchemaBuilder $builder);
}
