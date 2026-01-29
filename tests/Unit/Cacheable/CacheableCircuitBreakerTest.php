<?php

namespace Tests\Unit\Cacheable;

use CircuitBreakerBundle\CacheableCircuitBreaker;
use Nyholm\BundleTest\TestKernel;
use Tests\KernelTestCase;

class CacheableCircuitBreakerTest extends KernelTestCase
{
    public function testCacheable(): void
    {
        $kernel = self::bootKernel(['config' => static function (TestKernel $kernel) {
            $kernel->addTestConfig(__DIR__ . '/config/testCacheable.config.yaml');
        }]);

        $name = __CLASS__ . __METHOD__;
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
