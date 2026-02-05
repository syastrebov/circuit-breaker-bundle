<?php

namespace Tests\Unit\Cacheable;

use CircuitBreakerBundle\CacheableCircuitBreaker;
use Tests\KernelTestCase;

final class CacheableCircuitBreakerTest extends KernelTestCase
{
    public function testCacheable(): void
    {
        $name = __CLASS__ . __METHOD__;
        $kernel = $this->bootKernelFromConfig(__DIR__, 'testCacheable');
        $container = $kernel->getContainer();

        $circuit = $container->get('circuit_breaker.api.cacheable');
        $this->assertInstanceOf(CacheableCircuitBreaker::class, $circuit);

        $response = $circuit->run($name, function () {
            return '{"response": "data"}';
        });

        $this->assertEquals('{"response": "data"}', $response);

        $response = $circuit->run($name, function () {
            throw new \RuntimeException('unable to fetch data');
        });

        $this->assertEquals('{"response": "data"}', $response);
    }
}
