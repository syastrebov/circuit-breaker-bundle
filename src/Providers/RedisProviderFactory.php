<?php

namespace CircuitBreakerBundle\Providers;

use CircuitBreaker\Contracts\ProviderInterface;
use CircuitBreaker\Providers\PredisProvider;
use CircuitBreaker\Providers\RedisProvider;
use CircuitBreakerBundle\Drivers\PredisFactory;
use CircuitBreakerBundle\Drivers\RedisDsnFactory;
use CircuitBreakerBundle\Drivers\RedisFactory;
use CircuitBreakerBundle\Model\RedisConfig;
use Predis\ClientInterface;

final readonly class RedisProviderFactory implements ProviderFactoryInterface
{
    public function create(array $config): ProviderInterface
    {
        $redisConfig = new RedisConfig($config);

        $connection = match (true) {
            !empty($redisConfig->getDsn())  => RedisDsnFactory::create($redisConfig),
            extension_loaded('redis') => RedisFactory::create($redisConfig),
            interface_exists(ClientInterface::class) => PredisFactory::create($redisConfig),
            default => throw new \RuntimeException('Redis is not installed.'),
        };

        return $connection instanceof ClientInterface
            ? new PredisProvider($connection)
            : new RedisProvider($connection);
    }
}
