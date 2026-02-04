<?php

namespace Tests\Unit\Provider\Memcached;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\Providers\MemcachedProvider;
use Tests\KernelTestCase;

class MemcachedTest extends KernelTestCase
{
    public function testMemcachedProvider(): void
    {
        $kernel = $this->bootKernelFromConfig(__DIR__, 'testMemcachedProvider');
        $container = $kernel->getContainer();

        $this->assertTrue($container->has('circuit_breaker.provider'));
        $this->assertInstanceOf(MemcachedProvider::class, $container->get('circuit_breaker.provider'));

        $circuitBreaker = $container->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);
        $this->assertInstanceOf(MemcachedProvider::class, $this->getProvider($circuitBreaker));
    }
}
