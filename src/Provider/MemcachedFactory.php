<?php

namespace CircuitBreakerBundle\Provider;

final class MemcachedFactory extends AbstractFactory
{
    public function create(): \Memcached
    {
        $memcached = new \Memcached();
        foreach ($this->config['servers'] ?? [] as $server) {
            $memcached->addServer($server['host'] ?? '127.0.0.1', $server['port'] ?? 11211);
        }

        return $memcached;
    }
}
