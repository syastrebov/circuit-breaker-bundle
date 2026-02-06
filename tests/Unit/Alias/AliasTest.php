<?php

namespace Tests\Unit\Alias;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\Contracts\CircuitBreakerInterface;
use CircuitBreakerBundle\CacheableCircuitBreaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\KernelTestCase;

class AliasTest extends KernelTestCase
{
    #[DataProvider('configs')]
    public function testSingleApiCacheableAlias(string $config, string $expectedPrefix): void
    {
        $kernel = $this->bootKernelFromConfig(__DIR__, $config);
        $container = $kernel->getContainer();

        $circuit = $container->get(CircuitBreakerInterface::class);
        $this->assertInstanceOf(CircuitBreaker::class, $circuit);
        $this->assertEquals($expectedPrefix, $circuit->getConfig()->prefix);

        $circuit = $container->get(CircuitBreaker::class);
        $this->assertInstanceOf(CircuitBreaker::class, $circuit);
        $this->assertEquals($expectedPrefix, $circuit->getConfig()->prefix);

        $circuit = $container->get(CacheableCircuitBreaker::class);
        $this->assertInstanceOf(CacheableCircuitBreaker::class, $circuit);
        $this->assertEquals($expectedPrefix, $circuit->getConfig()->prefix);
    }

    public static function configs(): array
    {
        return [
            ['testSingleApiCacheableAlias', 'api'],
            ['testSingleDefaultCacheableAlias', 'default'],
            ['testMultipleDefaultCacheableAlias', 'default'],
            ['testMultipleGetFirstCacheableAlias', 'api1'],
        ];
    }
}
