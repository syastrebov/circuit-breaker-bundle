<?php

namespace CircuitBreakerBundle\Providers;

use CircuitBreaker\Contracts\ProviderInterface;
use CircuitBreaker\Providers\PredisProvider;
use CircuitBreaker\Providers\RedisProvider;
use CircuitBreakerBundle\Drivers\PredisFactory;
use CircuitBreakerBundle\Drivers\RedisFactory;
use CircuitBreakerBundle\Model\RedisConfig;
use Predis\ClientInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

final readonly class RedisProviderFactory implements ProviderFactoryInterface
{
    public function create(array $config): ProviderInterface
    {
        $redisConfig = new RedisConfig($config);

        $connection = match (true) {
            !empty($redisConfig->getDsn())  => $this->createConnectionFromDsn($redisConfig),
            extension_loaded('redis') => RedisFactory::create($redisConfig),
            class_exists(ClientInterface::class) => PredisFactory::create($redisConfig),
            default => throw new \RuntimeException('Redis is not installed.'),
        };

        return $connection instanceof ClientInterface
            ? new PredisProvider($connection)
            : new RedisProvider($connection);
    }

    public function createConnectionFromDsn(RedisConfig $config): \Redis|\RedisCluster|ClientInterface
    {
        if (!class_exists(RedisAdapter::class)) {
            throw new \LogicException('Using "dsn" requires symfony/cache. Run "composer require symfony/cache".');
        }

        $connection = RedisAdapter::createConnection($config->getDsn());

        if (!$connection instanceof ClientInterface
            && !$connection instanceof \Redis
            && !$connection instanceof \RedisCluster
        ) {
            throw new \LogicException(sprintf(
                'Unsupported Redis client "%s".', get_debug_type($connection)
            ));
        }

        return $connection;
    }
}
