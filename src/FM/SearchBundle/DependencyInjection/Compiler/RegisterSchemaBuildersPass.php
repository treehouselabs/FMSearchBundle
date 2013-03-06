<?php

namespace FM\SearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class RegisterSchemaBuildersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('fm_search.schema_factory')) {
            return;
        }

        $definition = $container->getDefinition('fm_search.schema_factory');

        foreach ($container->findTaggedServiceIds('fm_search.schema_builder') as $id => $builderPasses) {
           foreach ($builderPasses as $builderPass) {
                if (!isset($builderPass['schema'])) {
                    throw new \LogicException('You need to define a schema for a service tagged with "fm_search.schema_builder');
                }

                $definition->addMethodCall('addSchemaBuilderPass', array(new Reference($id), $builderPass['schema']));
            }
        }
    }
}
