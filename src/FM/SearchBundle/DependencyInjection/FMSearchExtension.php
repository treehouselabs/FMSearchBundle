<?php

namespace FM\SearchBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class FMSearchExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        // remove data collector when we're not debugging
        if (!$container->getParameter('kernel.debug')) {
            $container->removeDefinition('fm_search.data_collector');
        }

        $this->loadSchemaMapping($config, $container);
        $this->loadSolariumClient($config, $container);
        $this->loadFormExtension($container);
    }

    protected function loadSchemaMapping(array $config, ContainerBuilder $container)
    {
        if ($config['auto_mapping']) {

            $dirs = array();

            foreach ($container->getParameter('kernel.bundles') as $name => $class) {
                $dir = $this->getBundleEntityDir(new \ReflectionClass($class));
                if (is_dir($dir)) {
                    $dirs[] = $dir;
                }
            }

        } elseif (isset($config['mappings']) && !empty($config['mappings'])) {

            $dirs = $config['mappings'];

        } else {
            throw new \LogicException('You must provide one or more mappings when "auto_mapping" is set to false');
        }

        $definition = $container->setDefinition('fm_search.schema.mapping.file_driver', new Definition('%fm_search.schema.mapping.file_driver.class%'));
        $definition->setArguments(array($dirs));

        $container->setAlias('fm_search.schema.mapping.driver', 'fm_search.schema.mapping.file_driver');

        $container->getDefinition('fm_search.schema_factory')->replaceArgument(0, new Reference('fm_search.schema.mapping.driver'));
    }

    protected function getBundleEntityDir(\ReflectionClass $bundle)
    {
        return sprintf('%s/%s', dirname($bundle->getFilename()), 'Entity');
    }

    protected function loadSolariumClient(array $config, ContainerBuilder $container)
    {
        if (isset($config['adapter'])) {
            if (isset($config['adapter_class'])) {
                throw new \LogicException('You cannot set both "adapter" and "adapter_class".');
            }
            $config['adapter_class'] = sprintf('Solarium\Core\Client\Adapter\%s', str_replace(' ', '', ucwords(strtr($config['adapter'], '_', ' '))));
        }

        if (isset($config['adapter_class'])) {
            if (!class_exists($config['adapter_class'])) {
                throw new \LogicException(sprintf('Adapter class "%s" could not be loaded', $config['adapter_class']));
            }
            if (!is_subclass_of($config['adapter_class'], '\Solarium\Core\Client\Adapter\AdapterInterface')) {
                throw new \LogicException(sprintf('Adapter class "%s" does not implement \Solarium\Core\Client\Adapter\AdapterInterface', $config['adapter_class']));
            }
        }

        if (isset($config['default_client'])) {
            if (!array_key_exists($config['default_client'], $config['clients'])) {
                throw new \LogicException(sprintf('No client named "%s" configured', $config['default_client']));
            }
        }

        $solariumConfig = array(
            'endpoint' => $config['clients']
        );

        $definition = $container->setDefinition('fm_search.client', new Definition($config['client_class']));
        $definition->setArguments(array($solariumConfig));

        if (isset($config['adapter_class'])) {
            $definition->addMethodCall('setAdapter', array($config['adapter_class']));
        }

        if (isset($config['default_client'])) {
            $definition->addMethodCall('setDefaultEndpoint', array($config['default_client']));
        }

        $logger = new Reference('fm_search.logger');
        $definition->addMethodCall('registerPlugin', array('logger', $logger));

        if ($container->hasDefinition('fm_search.data_collector')) {
            $definition->addMethodCall('registerPlugin', array('collector', new Reference('fm_search.data_collector')));
        }
    }

    protected function loadFormExtension(ContainerBuilder $container)
    {
        $resources = $container->hasParameter('twig.form.resources') ? $container->getParameter('twig.form.resources') : array();
        $resources[] = 'FMSearchBundle:Form:form_div_layout.html.twig';
        $container->setParameter('twig.form.resources', $resources);
    }
}
