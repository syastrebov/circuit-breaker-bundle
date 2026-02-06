<?php

namespace CircuitBreakerBundle\DependencyInjection;

use CircuitBreaker\Enums\Provider;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

final class Configuration
{
    public static function configure(DefinitionConfigurator $definition): void
    {
        $definition
            ->rootNode()
            ->children()
                ->enumNode('provider')
                    ->isRequired()
                    ->enumFqcn(Provider::class)
                ->end()
                ->stringNode('cache_pool')
                    ->cannotBeEmpty()
                ->end()
                ->stringNode('logger')
                    ->cannotBeEmpty()
                ->end()
                ->append(self::getConnectionsConfig())
                ->append(self::getConfigurationsConfig())
            ->end();
    }

    private static function getConnectionsConfig(): NodeDefinition
    {
        $builder = new TreeBuilder('connections');
        $node = $builder->getRootNode();
        $node
            ->children()
                ->append(self::getRedisConnectionConfig())
                ->append(self::getDatabaseConnectionConfig())
                ->append(self::getMemcachedConnectionConfig())
            ->end();

        return $node;
    }

    private static function getRedisConnectionConfig(): NodeDefinition
    {
        $builder = new TreeBuilder('redis');
        $node = $builder->getRootNode();
        $node
            ->validate()
                ->ifTrue(function (array $v): bool {
                    return empty($v['host']) && empty($v['nodes']);
                })
                ->thenInvalid('You must set either "host" or "nodes".')
            ->end()
            ->children()
                ->scalarNode('host')->end()
                ->integerNode('port')->end()
                ->arrayNode('nodes')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('host')
                                ->isRequired()
                            ->end()
                            ->integerNode('port')->end()
                        ->end()
                    ->end()
                ->end()
                ->floatNode('timeout')->end()
                ->booleanNode('persistent')->end()
                ->scalarNode('persistent_id')->end()
                ->integerNode('retry_interval')->end()
                ->floatNode('read_timeout')->end()
                ->integerNode('max_retries')->end()
                ->scalarNode('username')->end()
                ->scalarNode('password')->end()
                ->integerNode('database')->end()
                ->scalarNode('prefix')->end()
                ->variableNode('context')
                    ->info('Arbitrary phpredis context (stream options, auth, etc.)')
                    ->defaultValue([])
                ->end()
            ->end();

        return $node;
    }

    private static function getDatabaseConnectionConfig(): NodeDefinition
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

    private static function getMemcachedConnectionConfig(): NodeDefinition
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

    private static function getConfigurationsConfig(): NodeDefinition
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
