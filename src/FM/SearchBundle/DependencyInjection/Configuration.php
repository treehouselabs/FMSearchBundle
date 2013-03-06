<?php

namespace FM\SearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fm_search');

        $rootNode
            ->fixXmlConfig('client')
            ->fixXmlConfig('accessor_type')
            ->children()
                ->arrayNode('clients')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->beforeNormalization()
                            ->ifTrue(function($v) { return isset($v['dsn']) && !parse_url($v['dsn']); })
                            ->thenInvalid('dsn %s could not be parsed')
                            ->ifTrue(function($v) { return isset($v['dsn']); })
                            ->then(function($v) {
                                $dsn = parse_url($v['dsn']);
                                unset($v['dsn']);

                                if (isset($dsn['host'])) {
                                    $v['host'] = $dsn['host'];
                                }

                                $v['port'] = isset($dsn['port']) ? $dsn['port'] : 8080;
                                $v['path'] = isset($dsn['path']) ? $dsn['path'] : '';

                                return $v;
                            })
                        ->end()
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('host')->defaultValue('127.0.0.1')->end()
                            ->scalarNode('port')->defaultValue(8080)->end()
                            ->scalarNode('path')->cannotBeEmpty()->end()
                            ->scalarNode('core')->end()
                            ->scalarNode('timeout')->defaultValue(5)->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('accessor_types')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->beforeNormalization()
                            ->ifString()
                            ->then(function($v) { return array('class' => $v); })
                        ->end()
                        ->children()
                            ->scalarNode('class')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()

                ->scalarNode('default_client')->end()
                ->scalarNode('auto_mapping')->defaultTrue()->end()
                ->arrayNode('mappings')->end()

                ->scalarNode('client_class')->cannotBeEmpty()->defaultValue('Solarium\Client')->end()
                ->enumNode('adapter')
                    ->values(array('curl', 'http', 'pecl_http', 'zend_http'))
                ->end()
                ->scalarNode('adapter_class')->cannotBeEmpty()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
