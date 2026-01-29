<?php

namespace CircuitBreakerBundle\DependencyInjection;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\CircuitBreakerConfig;
use CircuitBreaker\Provider\DatabaseProvider;
use CircuitBreaker\Provider\MemcachedProvider;
use CircuitBreaker\Provider\ProviderInterface;
use CircuitBreaker\Provider\MemoryProvider;
use CircuitBreaker\Provider\RedisProvider;
use CircuitBreakerBundle\Provider\MemcachedFactory;
use CircuitBreakerBundle\Provider\RedisFactory;
use Psr\Log\LoggerInterface;
use CircuitBreakerBundle\Enums\Provider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class CircuitBreakerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // if no configurations defined use default one
        if (empty($config['configurations'])) {
            $config['configurations']['default'] = [];
        }

        $this->loadProvider($container, $config['provider'], $config['connections'] ?? []);
        $this->loadConfigurations($container, $config['configurations']);
        $this->loadServices($container, $config['configurations']);
    }

    private function loadProvider(ContainerBuilder $container, Provider $provider, array $connections): void
    {
        $definition = $container->register('circuit_breaker.provider', match ($provider) {
            Provider::Redis => RedisProvider::class,
            Provider::Memcached => MemcachedProvider::class,
            Provider::Database => DatabaseProvider::class,
            Provider::Memory => MemoryProvider::class,
        })->setPublic(true);

        if ($provider === Provider::Redis) {
            $redisDefinition = new Definition();
            $redisDefinition->setFactory([RedisFactory::class, 'create']);
            $redisDefinition->addArgument($connections['redis']);

            $definition->setArguments([$redisDefinition]);
        }
        if ($provider === Provider::Memcached) {
            $memcachedDefinition = new Definition();
            $memcachedDefinition->setFactory([MemcachedFactory::class, 'create']);
            $memcachedDefinition->setArguments([$connections['memcached']['servers'] ?? []]);

            $definition->setArguments([$memcachedDefinition]);
        }
        if ($provider === Provider::Database) {
            $connection = $connections['database']['connection'] ?? 'default';
            $table = $connections['database']['table'] ?? 'circuit_breaker';

            $pdoDefinition = new Definition(\PDO::class);
            $pdoDefinition->setFactory([
                new Reference("doctrine.dbal.{$connection}_connection"),
                'getNativeConnection'
            ]);

            $definition->setArguments([$pdoDefinition, $table]);
        }

        $container->setAlias(ProviderInterface::class, 'circuit_breaker.provider');
    }

    private function loadConfigurations(ContainerBuilder $container, array $configurations): void
    {
        foreach ($configurations as $name => $config) {
            $container
                ->register("circuit_breaker.$name.config", CircuitBreakerConfig::class)
                ->setArguments([
                    $config["retries"] ?? 3,
                    $config["closed_threshold"] ?? 5,
                    $config["half_open_threshold"] ?? 5,
                    $config["retry_interval"] ?? 1000,
                    $config["open_timeout"] ?? 60,
                    $config["fallback_or_null"] ?? false,
                ]);
        }
    }

    private function loadServices(ContainerBuilder $container, array $configurations): void
    {
        foreach (array_keys($configurations) as $name) {
            $container
                ->register("circuit_breaker.$name", CircuitBreaker::class)
                ->setArguments([
                    new Reference(ProviderInterface::class),
                    new Reference("circuit_breaker.$name.config"),
                    new Reference(LoggerInterface::class),
                ])->setPublic(true);
        }

        if ($container->has('circuit_breaker.default')) {
            $container->setAlias(CircuitBreaker::class, 'circuit_breaker.default');
        }
    }
}
