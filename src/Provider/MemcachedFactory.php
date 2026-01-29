<?php

namespace CircuitBreakerBundle\Provider;

class MemcachedFactory
{
    public static function create(array $servers): \Memcached
    {
        $memcached = new \Memcached();
        foreach ($servers as $server) {
            $memcached->addServer($server['host'], $server['port'] ?? 11211);
        }

        return $memcached;
    }
}
