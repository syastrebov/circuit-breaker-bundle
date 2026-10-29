<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\CircuitBreakerConfig;
use CircuitBreaker\Contracts\ProviderInterface;
use CircuitBreakerBundle\CacheableCircuitBreaker;
use CircuitBreakerBundle\Providers\DatabaseProviderFactory;
use CircuitBreakerBundle\Providers\MemcachedProviderFactory;
use CircuitBreakerBundle\Providers\MemoryProviderFactory;
use CircuitBreakerBundle\Providers\RedisProviderFactory;

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
        ->set('circuit_breaker.provider_factory.redis', RedisProviderFactory::class)
        ->set('circuit_breaker.provider_factory.memcached', MemcachedProviderFactory::class)
        ->set('circuit_breaker.provider_factory.database', DatabaseProviderFactory::class)
            ->args([service('doctrine')->nullOnInvalid()])
        ->set('circuit_breaker.provider_factory.memory', MemoryProviderFactory::class);
};
