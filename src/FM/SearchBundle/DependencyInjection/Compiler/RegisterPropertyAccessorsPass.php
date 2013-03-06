<?php

namespace FM\SearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class RegisterPropertyAccessorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('fm_search.registry')) {
            return;
        }

        $definition = $container->getDefinition('fm_search.registry');

        foreach ($container->findTaggedServiceIds('fm_search.property_accessor') as $id => $accessors) {
           foreach ($accessors as $accessor) {
                if (!isset($accessor['type'])) {
                    throw new \LogicException('You need to define a type for a service tagged with "fm_search.property_accessor');
                }

                $definition->addMethodCall('registerType', array('accessor', $accessor['type'], new Reference($id)));
            }
        }
    }
}
