<?php

namespace CircuitBreakerBundle\Provider;

class RedisFactory
{
    public static function create(array $config): \Redis|\RedisCluster
    {
        return !empty($config['nodes'])
            ? self::createCluster($config)
            : self::createSingle($config);
    }

    public static function createSingle(array $config): \Redis
    {
        $redis = new \Redis();
        $persistent = $config['persistent'] ?? false;

        $parameters = [
            $config['host'],
            $config['port'] ?? 6379,
            $config['timeout'] ?? 0.0,
            $persistent ? $config['persistent_id'] ?? null : null,
            $config['retry_interval'] ?? 0,
        ];

        if (version_compare(phpversion('redis'), '3.1.3', '>=')) {
            $parameters[] = $config['read_timeout'] ?? 0.0;
        }

        if (
            version_compare(phpversion('redis'), '5.3.0', '>=')
            && !is_null($context = $config['context'] ?? null)
        ) {
            $parameters[] = $context;
        }

        $redis->{$persistent ? 'pconnect' : 'connect'}(...$parameters);

        if (array_key_exists('max_retries', $config)) {
            $redis->setOption(\Redis::OPT_MAX_RETRIES, $config['max_retries']);
        }

        if (!empty($config['password'])) {
            if (isset($config['username']) && $config['username'] !== '' && is_string($config['password'])) {
                $redis->auth([$config['username'], $config['password']]);
            } else {
                $redis->auth($config['password']);
            }
        }

        if (isset($config['database'])) {
            $redis->select((int) $config['database']);
        }

        if (!empty($config['prefix'])) {
            $redis->setOption(\Redis::OPT_PREFIX, $config['prefix']);
        }

        if (!empty($config['read_timeout'])) {
            $redis->setOption(\Redis::OPT_READ_TIMEOUT, $config['read_timeout']);
        }

        return $redis;
    }

    public static function createCluster(array $config): \RedisCluster
    {
        $parameters = [
            null,
            array_map(
                fn (array $node) => $node['host'] . ':' . $node['port'] ?? 6379,
                $config['nodes'] ?? []
            ),
            $config['timeout'] ?? 0,
            $config['read_timeout'] ?? 0,
            isset($config['persistent']) && $config['persistent'],
        ];

        if (version_compare(phpversion('redis'), '4.3.0', '>=')) {
            $parameters[] = $config['password'] ?? null;
        }

        $redisCluster = new \RedisCluster(...$parameters);

        if (!empty($config['prefix'])) {
            $redisCluster->setOption(\Redis::OPT_PREFIX, $config['prefix']);
        }

        return $redisCluster;
    }
}
