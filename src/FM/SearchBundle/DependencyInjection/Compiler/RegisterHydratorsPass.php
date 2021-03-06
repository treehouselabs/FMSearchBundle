<?php

namespace FM\SearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterHydratorsPass implements CompilerPassInterface
{
    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('fm_search.document_manager')) {
            return;
        }

        $definition = $container->getDefinition('fm_search.document_manager');

        foreach ($container->findTaggedServiceIds('fm_search.hydrator') as $id => $hydrators) {
            foreach ($hydrators as $hydrator) {
                if (!isset($hydrator['mode'])) {
                    throw new \LogicException('You need to define a mode for a service tagged with "fm_search.hydrator');
                }

                $definition->addMethodCall('registerHydrator', [new Reference($id), $hydrator['mode']]);
            }
        }
    }
}
