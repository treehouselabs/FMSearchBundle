<?php

namespace FM\SearchBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use FM\SearchBundle\DependencyInjection\Compiler;

class FMSearchBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new Compiler\RegisterSchemaBuildersPass());
        $container->addCompilerPass(new Compiler\RegisterPropertyAccessorsPass());
        $container->addCompilerPass(new Compiler\RegisterHydratorsPass());
        $container->addCompilerPass(new Compiler\RegisterEventListenersPass());
    }
}
