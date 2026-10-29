<?php

namespace CircuitBreakerBundle\Providers;

use CircuitBreaker\Contracts\ProviderInterface;
use CircuitBreaker\Providers\MemcachedProvider;

final readonly class MemcachedProviderFactory implements ProviderFactoryInterface
{
    public function create(array $config): ProviderInterface
    {
        return new MemcachedProvider($this->createConnection($config));
    }

    private function createConnection(array $config): \Memcached
    {
        if (!extension_loaded('memcached')) {
            throw new \RuntimeException('Memcached extension is not loaded.');
        }

        $servers = $config['servers'] ?? [];
        if ($servers === []) {
            throw new \InvalidArgumentException('Memcached requires at least one server.');
        }

        $memcached = new \Memcached();
        foreach ($servers as $server) {
            $memcached->addServer($server['host'] ?? '127.0.0.1', $server['port'] ?? 11211);
        }

        return $memcached;
    }
}
