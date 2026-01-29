<?php

namespace CircuitBreakerBundle\DependencyInjection;

use CircuitBreakerBundle\Enums\Provider;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('circuit_breaker');
        $treeBuilder
            ->getRootNode()
            ->children()
                ->enumNode('provider')
                    ->isRequired()
                    ->enumFqcn(Provider::class)
                ->end()
                ->append($this->getConnectionsConfig())
                ->append($this->getConfigurationsConfig())
            ->end();

        return $treeBuilder;
    }

    private function getConnectionsConfig(): NodeDefinition
    {
        $builder = new TreeBuilder('connections');
        $node = $builder->getRootNode();
        $node
            ->children()
                ->append($this->getRedisConnectionConfig())
                ->append($this->getDatabaseConnectionConfig())
                ->append($this->getMemcachedConnectionConfig())
            ->end();

        return $node;
    }

    private function getRedisConnectionConfig(): NodeDefinition
    {
        $builder = new TreeBuilder('redis');
        $node = $builder->getRootNode();
        $node
            ->children()
                ->scalarNode('host')->isRequired()->end()
                ->integerNode('port')->end()
                ->floatNode('timeout')->end()
                ->scalarNode('persistent_id')->end()
                ->integerNode('retry_interval')->end()
                ->floatNode('read_timeout')->end()
                ->integerNode('max_retries')->end()
                ->scalarNode('username')->end()
                ->scalarNode('password')->end()
                ->integerNode('database')->end()
                ->scalarNode('prefix')->end()
                ->integerNode('read_timeout')->end()
            ->end();

        return $node;
    }

    private function getDatabaseConnectionConfig(): NodeDefinition
    {
        $builder = new TreeBuilder('database');
        $node = $builder->getRootNode();
        $node
            ->children()
                ->scalarNode('connection')->end()
                ->scalarNode('table')
                    ->cannotBeEmpty()
                ->end()
            ->end();

        return $node;
    }

    private function getMemcachedConnectionConfig(): NodeDefinition
    {
        $builder = new TreeBuilder('memcached');
        $node = $builder->getRootNode();
        $node
            ->children()
                ->arrayNode('servers')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('host')->end()
                            ->integerNode('port')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    private function getConfigurationsConfig(): NodeDefinition
    {
        $builder = new TreeBuilder('configurations');
        $node = $builder->getRootNode();
        $node
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->integerNode('retries')
                        ->min(1)
                        ->defaultValue(3)
                    ->end()
                    ->integerNode('closed_threshold')
                        ->min(1)
                        ->defaultValue(3)
                    ->end()
                    ->integerNode('half_open_threshold')
                        ->min(1)
                        ->defaultValue(3)
                    ->end()
                    ->integerNode('retry_interval')
                        ->min(1000)
                        ->defaultValue(1000)
                    ->end()
                    ->integerNode('open_timeout')
                        ->min(1)
                        ->defaultValue(60)
                    ->end()
                    ->booleanNode('fallback_or_null')
                        ->defaultValue(false)
                    ->end()
                ->end()
            ->end();

        return $node;
    }
}
