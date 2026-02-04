<?php

namespace Tests\Unit\Config;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\CircuitBreakerConfig;
use Nyholm\BundleTest\TestKernel;
use Tests\KernelTestCase;

class ConfigTest extends KernelTestCase
{
    public function testEmptyConfig(): void
    {
        $kernel = self::bootKernel(['config' => static function (TestKernel $kernel) {
            $kernel->addTestConfig(__DIR__ . '/config/testEmptyConfig.config.yaml');
        }]);

        $container = $kernel->getContainer();

        $this->assertTrue($container->has('circuit_breaker.default'));

        $circuitBreaker = $container->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);

        $config = $circuitBreaker->getConfig();

        $this->assertEquals(3, $config->retries);
        $this->assertEquals(3, $config->closedThreshold);
        $this->assertEquals(3, $config->halfOpenThreshold);
        $this->assertEquals(1000, $config->retryInterval);
        $this->assertEquals(60, $config->openTimeout);
        $this->assertFalse($config->fallbackOrNull);
    }

    public function testDefaultConfig(): void
    {
        $kernel = self::bootKernel(['config' => static function (TestKernel $kernel) {
            $kernel->addTestConfig(__DIR__ . '/config/testDefaultConfig.config.yaml');
        }]);

        $circuitBreaker = $kernel->getContainer()->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);

        $config = $circuitBreaker->getConfig();

        $this->assertEquals(3, $config->retries);
        $this->assertEquals(3, $config->closedThreshold);
        $this->assertEquals(3, $config->halfOpenThreshold);
        $this->assertEquals(1000, $config->retryInterval);
        $this->assertEquals(60, $config->openTimeout);
        $this->assertFalse($config->fallbackOrNull);
    }

    public function testCustomConfig(): void
    {
        $kernel = self::bootKernel(['config' => static function (TestKernel $kernel) {
            $kernel->addTestConfig(__DIR__ . '/config/testCustomConfig.config.yaml');
        }]);

        $circuitBreaker = $kernel->getContainer()->get('circuit_breaker.api');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);

        $config = $circuitBreaker->getConfig();

        $this->assertEquals(2, $config->retries);
        $this->assertEquals(5, $config->closedThreshold);
        $this->assertEquals(10, $config->halfOpenThreshold);
        $this->assertEquals(3000, $config->retryInterval);
        $this->assertEquals(120, $config->openTimeout);
        $this->assertTrue($config->fallbackOrNull);
    }

    public function testMultipleConfigs(): void
    {
        $kernel = self::bootKernel(['config' => static function (TestKernel $kernel) {
            $kernel->addTestConfig(__DIR__ . '/config/testMultipleConfigs.config.yaml');
        }]);

        $defaultCircuitBreaker = $kernel->getContainer()->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $defaultCircuitBreaker);

        $config = $defaultCircuitBreaker->getConfig();

        $this->assertEquals(3, $config->retries);
        $this->assertEquals(3, $config->closedThreshold);
        $this->assertEquals(3, $config->halfOpenThreshold);
        $this->assertEquals(1000, $config->retryInterval);
        $this->assertEquals(60, $config->openTimeout);
        $this->assertFalse($config->fallbackOrNull);

        $apiCircuitBreaker = $kernel->getContainer()->get('circuit_breaker.api');
        $this->assertInstanceOf(CircuitBreaker::class, $apiCircuitBreaker);

        $config = $apiCircuitBreaker->getConfig();

        $this->assertEquals(2, $config->retries);
        $this->assertEquals(5, $config->closedThreshold);
        $this->assertEquals(10, $config->halfOpenThreshold);
        $this->assertEquals(3000, $config->retryInterval);
        $this->assertEquals(120, $config->openTimeout);
        $this->assertTrue($config->fallbackOrNull);
    }
}
