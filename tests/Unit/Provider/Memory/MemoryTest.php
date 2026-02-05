<?php

namespace Tests\Unit\Provider\Memory;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\Providers\MemoryProvider;
use Tests\KernelTestCase;

final class MemoryTest extends KernelTestCase
{
    public function testMemoryProvider(): void
    {
        $kernel = $this->bootKernelFromConfig(__DIR__, 'testMemoryProvider');
        $container = $kernel->getContainer();

        $this->assertTrue($container->has('circuit_breaker.provider'));
        $this->assertInstanceOf(MemoryProvider::class, $container->get('circuit_breaker.provider'));

        $this->assertTrue($container->has('circuit_breaker.default'));

        $circuitBreaker = $container->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);
        $this->assertInstanceOf(MemoryProvider::class, $this->getProvider($circuitBreaker));
    }
}
