<?php

namespace CircuitBreakerBundle\Provider;

final class RedisFactory extends AbstractRedisFactory
{
    public function create(): \Redis|\RedisCluster
    {
        if (!extension_loaded('redis')) {
            throw new \RuntimeException('Redis extension is not loaded.');
        }

        return !empty($this->getNodes())
            ? $this->createCluster()
            : $this->createSingle();
    }

    public function createSingle(): \Redis
    {
        $redis = new \Redis();

        $parameters = [
            $this->getHost(),
            $this->getPort(),
            $this->getTimeout(),
            $this->getPersistentId(),
            $this->getRetryInterval(),
        ];

        if (
            self::getVersion() && version_compare(self::getVersion(), '5.3.0', '>=')
            && $this->getContext()
        ) {
            $parameters[] = $this->getContext();
        }

        $redis->{$this->isPersistent() ? 'pconnect' : 'connect'}(...$parameters);

        if (is_int($this->getMaxRetries())) {
            $redis->setOption(\Redis::OPT_MAX_RETRIES, $this->getMaxRetries());
        }

        if ($this->getPassword()) {
            if ($this->getUsername()) {
                /** @psalm-suppress InvalidCast */
                /** @psalm-suppress InvalidArgument */
                $redis->auth([$this->getUsername(), $this->getPassword()]);
            } else {
                $redis->auth($this->getPassword());
            }
        }

        if (is_int($this->getDatabase())) {
            $redis->select($this->getDatabase());
        }

        if ($this->getPrefix()) {
            $redis->setOption(\Redis::OPT_PREFIX, $this->getPrefix());
        }

        if ($this->getReadTimeout()) {
            $redis->setOption(\Redis::OPT_READ_TIMEOUT, $this->getReadTimeout());
        }

        return $redis;
    }

    public function createCluster(): \RedisCluster
    {
        $parameters = [
            null,
            $this->getNodes(),
            $this->getTimeout(),
            $this->getReadTimeout(),
            $this->isPersistent(),
        ];

        if (self::getVersion() && version_compare(self::getVersion(), '4.3.0', '>=')) {
            $parameters[] = $this->getPassword();
        }

        $redisCluster = new \RedisCluster(...$parameters);

        if ($this->getPrefix()) {
            $redisCluster->setOption(\Redis::OPT_PREFIX, $this->getPrefix());
        }

        return $redisCluster;
    }

    private static function getVersion(): string
    {
        return (string) phpversion('redis');
    }
}
