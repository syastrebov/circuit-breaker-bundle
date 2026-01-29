<?php

namespace CircuitBreakerBundle\Provider;

class RedisFactory
{
    public static function create(array $config): \Redis
    {
        $redis = new \Redis();
        $redis->connect($config['host'], $config['port']);

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

        if (version_compare(phpversion('redis'), '5.3.0', '>=')
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

        if (! empty($config['prefix'])) {
            $redis->setOption(\Redis::OPT_PREFIX, $config['prefix']);
        }

        if (! empty($config['read_timeout'])) {
            $redis->setOption(\Redis::OPT_READ_TIMEOUT, $config['read_timeout']);
        }

        return $redis;
    }

    public static function createCluster(array $nodes): \RedisCluster
    {
        $redisCluster = new \RedisCluster(
            'my cluster',
            $nodes,
            1.5,
            1.5,
            true
        );

        return $redisCluster;
    }
}
