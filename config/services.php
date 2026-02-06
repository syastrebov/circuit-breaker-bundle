<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\CircuitBreakerConfig;
use CircuitBreaker\Contracts\CircuitBreakerInterface;
use CircuitBreaker\Contracts\ProviderInterface;
use CircuitBreaker\Providers\DatabaseProvider;
use CircuitBreaker\Providers\MemcachedProvider;
use CircuitBreaker\Providers\MemoryProvider;
use CircuitBreaker\Providers\PredisProvider;
use CircuitBreaker\Providers\RedisProvider;
use CircuitBreakerBundle\CacheableCircuitBreaker;
use CircuitBreakerBundle\EventListener\SchemaFilterListener;
use CircuitBreakerBundle\Provider\MemcachedFactory;
use CircuitBreakerBundle\Provider\PredisFactory;
use CircuitBreakerBundle\Provider\RedisFactory;
use Predis\Client;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->alias(ProviderInterface::class, 'circuit_breaker.provider')
            ->public()

        ->set('circuit_breaker.config.abstract', CircuitBreaker::class)
            ->abstract()
            ->factory([CircuitBreakerConfig::class, 'create'])
            ->public()

        ->set('circuit_breaker.abstract', CircuitBreaker::class)
            ->abstract()
            ->arg('$provider', service('circuit_breaker.provider'))
            ->arg('$logger', service('circuit_breaker.logger')->nullOnInvalid())
            ->public()

        ->set('circuit_breaker.cacheable.abstract', CacheableCircuitBreaker::class)
            ->abstract()
            ->arg('$logger', service('circuit_breaker.logger')->nullOnInvalid())
            ->public()

        // providers
        ->set('circuit_breaker.provider.redis', RedisProvider::class)
            ->args([service('circuit_breaker.driver.redis')])

        ->set('circuit_breaker.provider.predis', PredisProvider::class)
            ->args([service('circuit_breaker.driver.predis')])

        ->set('circuit_breaker.provider.memcached', MemcachedProvider::class)
            ->args([service('circuit_breaker.driver.memcached')])

        ->set('circuit_breaker.provider.database', DatabaseProvider::class)
            ->args([
                service('circuit_breaker.driver.database'),
                param('circuit_breaker.driver.database.table')
            ])

        ->set('circuit_breaker.provider.memory', MemoryProvider::class)

        // drivers
        ->set('circuit_breaker.driver.redis_factory', RedisFactory::class)

        ->set('circuit_breaker.driver.redis', \Redis::class)
        ->factory([service('circuit_breaker.driver.redis_factory'), 'create'])

        ->set('circuit_breaker.driver.predis_factory', PredisFactory::class)

        ->set('circuit_breaker.driver.predis', Client::class)
        ->factory([service('circuit_breaker.driver.predis_factory'), 'create'])

        ->set('circuit_breaker.driver.memcached_factory', MemcachedFactory::class)

        ->set('circuit_breaker.driver.memcached', \Memcached::class)
        ->factory([service('circuit_breaker.driver.memcached_factory'), 'create'])

        ->set('circuit_breaker.driver.database', \PDO::class)

        // listeners
        ->set('circuit_breaker.schema_filter_listener', SchemaFilterListener::class)
        ->tag('kernel.event_listener', [
            'event' => 'console.command',
            'method' => 'onConsoleCommand',
        ]);
};
