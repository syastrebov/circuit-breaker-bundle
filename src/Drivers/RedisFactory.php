<?php

namespace CircuitBreakerBundle\Drivers;

use CircuitBreakerBundle\Model\RedisConfig;

final class RedisFactory
{
    public static function create(RedisConfig $config): \Redis|\RedisCluster
    {
        if (!extension_loaded('redis')) {
            throw new \RuntimeException('Redis extension is not loaded.');
        }

        return !empty($config->getNodes())
            ? self::createCluster($config)
            : self::createSingle($config);
    }

    public static function createSingle(RedisConfig $config): \Redis
    {
        $redis = new \Redis();

        $parameters = [
            $config->getHost(),
            $config->getPort(),
            $config->getTimeout(),
            $config->getPersistentId(),
            $config->getRetryInterval(),
        ];

        if (
            self::getVersion() && version_compare(self::getVersion(), '5.3.0', '>=')
            && $config->getContext()
        ) {
            $parameters[] = $config->getContext();
        }

        $redis->{$config->isPersistent() ? 'pconnect' : 'connect'}(...$parameters);

        if (is_int($config->getMaxRetries())) {
            $redis->setOption(\Redis::OPT_MAX_RETRIES, $config->getMaxRetries());
        }

        if ($config->getPassword()) {
            if ($config->getUsername()) {
                /** @psalm-suppress InvalidCast */
                /** @psalm-suppress InvalidArgument */
                $redis->auth([$config->getUsername(), $config->getPassword()]);
            } else {
                $redis->auth($config->getPassword());
            }
        }

        if (is_int($config->getDatabase())) {
            $redis->select($config->getDatabase());
        }

        if ($config->getPrefix()) {
            $redis->setOption(\Redis::OPT_PREFIX, $config->getPrefix());
        }

        if ($config->getReadTimeout()) {
            $redis->setOption(\Redis::OPT_READ_TIMEOUT, $config->getReadTimeout());
        }

        return $redis;
    }

    public static function createCluster(RedisConfig $config): \RedisCluster
    {
        $parameters = [
            null,
            $config->getNodes(),
            $config->getTimeout(),
            $config->getReadTimeout(),
            $config->isPersistent(),
        ];

        if (self::getVersion() && version_compare(self::getVersion(), '4.3.0', '>=')) {
            $parameters[] = $config->getPassword();
        }

        $redisCluster = new \RedisCluster(...$parameters);

        if ($config->getPrefix()) {
            $redisCluster->setOption(\Redis::OPT_PREFIX, $config->getPrefix());
        }

        return $redisCluster;
    }

    private static function getVersion(): string
    {
        return (string) phpversion('redis');
    }
}
