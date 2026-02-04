<?php

namespace CircuitBreakerBundle;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\CircuitBreakerConfig;
use CircuitBreaker\Contracts\CircuitBreakerInterface;
use CircuitBreaker\Providers\DatabaseProvider;
use CircuitBreaker\Providers\MemcachedProvider;
use CircuitBreaker\Providers\MemoryProvider;
use CircuitBreaker\Providers\ProviderInterface;
use CircuitBreaker\Providers\RedisProvider;
use CircuitBreakerBundle\DependencyInjection\Configuration;
use CircuitBreakerBundle\Enums\Provider;
use CircuitBreakerBundle\EventListener\SchemaFilterListener;
use CircuitBreakerBundle\Provider\MemcachedFactory;
use CircuitBreakerBundle\Provider\RedisFactory;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class CircuitBreakerBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        Configuration::configure($definition);
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // if no configurations defined use default one
        $config['connections'] ??= [];
        if (empty($config['configurations'])) {
            $config['configurations']['default'] = [];
        }

        $this->loadProvider($config, $container);
        $this->loadConfigurations($config, $container);
        $this->loadServices($config, $container);
        $this->loadSchemaFilter($config, $container, $builder);
    }

    private function loadProvider(array $config, ContainerConfigurator $container): void
    {
        $definition = $container
            ->services()
            ->set('circuit_breaker.provider', match ($config['provider']) {
                Provider::Redis => RedisProvider::class,
                Provider::Memcached => MemcachedProvider::class,
                Provider::Database => DatabaseProvider::class,
                Provider::Memory => MemoryProvider::class,
            })
            ->public();

        switch ($config['provider']) {
            case Provider::Redis:
                $redisDefinition = (new Definition())
                    ->setFactory([RedisFactory::class, 'create'])
                    ->addArgument($config['connections']['redis'] ?? []);

                $definition->args([$redisDefinition]);

                break;
            case Provider::Memcached:
                $memcachedDefinition = (new Definition())
                    ->setFactory([MemcachedFactory::class, 'create'])
                    ->setArguments([$config['connections']['memcached']['servers'] ?? []]);

                $definition->args([$memcachedDefinition]);

                break;
            case Provider::Database:
                $connection = $config['connections']['database']['connection'] ?? 'default';
                $table = $this->getDatabaseTableName($config);

                $pdoDefinition = (new Definition(\PDO::class))
                    ->setFactory([
                        new Reference("doctrine.dbal.{$connection}_connection"),
                        'getNativeConnection'
                    ]);

                $definition->args([$pdoDefinition, $table]);

                break;
        }

        $container->services()->alias(ProviderInterface::class, 'circuit_breaker.provider');
        $container->parameters()->set('circuit_breaker.database.table', $this->getDatabaseTableName($config));
    }

    private function getDatabaseTableName(array $config): string
    {
        return $config['connections']['database']['table'] ?? 'circuit_breaker';
    }

    private function loadConfigurations(array $config, ContainerConfigurator $container): void
    {
        foreach ($config['configurations'] as $name => $config) {
            $container
                ->services()
                ->set("circuit_breaker.$name.config", CircuitBreakerConfig::class)
                ->factory([CircuitBreakerConfig::class, 'create'])
                ->args([[
                    ...$config,
                    'prefix' => $name,
                ]]);
        }
    }

    private function loadServices(array $config, ContainerConfigurator $container): void
    {
        $cachePool = $config['cache_pool'] ?? null;

        foreach (array_keys($config['configurations']) as $name) {
            $container
                ->services()
                ->set("circuit_breaker.$name", CircuitBreaker::class)
                ->args([
                    new Reference(ProviderInterface::class),
                    new Reference("circuit_breaker.$name.config"),
                    !empty($config['logger']) ? new Reference($config['logger']) : null,
                ])
                ->public();

            if ($cachePool) {
                $container
                    ->services()
                    ->set("circuit_breaker.$name.cacheable", CacheableCircuitBreaker::class)
                    ->args([
                        new Reference("circuit_breaker.$name"),
                        new Reference($cachePool),
                        !empty($config['logger']) ? new Reference($config['logger']) : null,
                    ])
                    ->public();
            }
        }

        if (count($config['configurations']) > 0) {
            $default = isset($config['configurations']['default'])
                ? 'default'
                : array_keys($config['configurations'])[0];

            $container->services()->alias(CircuitBreakerInterface::class, "circuit_breaker.$default");
        }
    }

    private function loadSchemaFilter(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // add filter to ignore this table
        $definition = $container
            ->services()
            ->set('circuit_breaker.schema_filter_listener', SchemaFilterListener::class)
            ->args([$this->getDatabaseTableName($config)]);

        if ($builder->hasParameter('doctrine.connections')) {
            /** @var array<string, string> $connections */
            $connections = $builder->getParameter('doctrine.connections');
            foreach (array_keys($connections) as $connection) {
                $definition->tag('doctrine.dbal.schema_filter', [
                    'connection' => $connection,
                ]);
                $definition->tag('kernel.event_listener', [
                    'event' => 'console.command',
                    'method' => 'onConsoleCommand',
                ]);
            }
        }
    }
}
