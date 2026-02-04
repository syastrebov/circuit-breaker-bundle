<?php

namespace Tests\Unit\Config;

use CircuitBreaker\CircuitBreaker;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\KernelTestCase;

class ConfigTest extends KernelTestCase
{
    public function testEmptyConfig(): void
    {
        $kernel = $this->bootKernelFromConfig(__DIR__, 'testEmptyConfig');
        $this->assertConfig($kernel, 'default', 3, 3, 3, 1000, 60, false);
    }

    public function testDefaultConfig(): void
    {
        $kernel = $this->bootKernelFromConfig(__DIR__, 'testDefaultConfig');
        $this->assertConfig($kernel, 'default', 3, 3, 3, 1000, 60, false);
    }

    public function testCustomConfig(): void
    {
        $kernel = $this->bootKernelFromConfig(__DIR__, 'testCustomConfig');
        $this->assertConfig($kernel, 'api', 2, 5, 10, 3000, 120, true);
    }

    public function testMultipleConfigs(): void
    {
        $kernel = $this->bootKernelFromConfig(__DIR__, 'testMultipleConfigs');

        $this->assertConfig($kernel, 'default', 3, 3, 3, 1000, 60, false);
        $this->assertConfig($kernel, 'api', 2, 5, 10, 3000, 120, true);
    }

    private function assertConfig(
        KernelInterface $kernel,
        string $prefix,
        int $retries,
        int $closedThreshold,
        int $halfOpenThreshold,
        int $retryInterval,
        int $openTimeout,
        bool $fallbackOrNull,
    ): void {
        $circuit = $kernel->getContainer()->get("circuit_breaker.$prefix");
        $this->assertInstanceOf(CircuitBreaker::class, $circuit);

        $config = $circuit->getConfig();

        $this->assertEquals($prefix, $config->prefix);
        $this->assertEquals($retries, $config->retries);
        $this->assertEquals($closedThreshold, $config->closedThreshold);
        $this->assertEquals($halfOpenThreshold, $config->halfOpenThreshold);
        $this->assertEquals($retryInterval, $config->retryInterval);
        $this->assertEquals($openTimeout, $config->openTimeout);
        $this->assertEquals($fallbackOrNull, $config->fallbackOrNull);
    }
}
