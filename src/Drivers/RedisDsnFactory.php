<?php

namespace CircuitBreakerBundle\Drivers;

use CircuitBreakerBundle\Model\RedisConfig;
use Predis\ClientInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

final class RedisDsnFactory
{
    public static function create(RedisConfig $config): \Redis|\RedisCluster|ClientInterface
    {
        if (!class_exists(RedisAdapter::class)) {
            throw new \LogicException(
                'Using "dsn" requires symfony/cache. Run "composer require symfony/cache".'
            );
        }

        $connection = RedisAdapter::createConnection($config->getDsn());

        if (
            !$connection instanceof ClientInterface
            && !$connection instanceof \Redis
            && !$connection instanceof \RedisCluster
        ) {
            throw new \LogicException(
                sprintf(
                    'Unsupported Redis client "%s".',
                    get_debug_type($connection),
                )
            );
        }

        return $connection;
    }
}
