<?php

namespace Tests\Unit\Provider\Database;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\Providers\DatabaseProvider;
use Tests\KernelTestCase;

class DatabaseTest extends KernelTestCase
{
    public function testImplicitlyDefaultDatabaseProvider(): void
    {
        $kernel = $this->bootKernelFromConfig(__DIR__, 'testImplicitlyDefaultDatabaseProvider');
        $container = $kernel->getContainer();

        $this->assertTrue($container->has('circuit_breaker.provider'));
        $this->assertInstanceOf(DatabaseProvider::class, $container->get('circuit_breaker.provider'));

        $this->assertTrue($container->has('circuit_breaker.default'));

        $circuitBreaker = $container->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);
        $this->assertInstanceOf(DatabaseProvider::class, $this->getProvider($circuitBreaker));
    }

    public function testDatabaseProviderPrimaryConnection(): void
    {
        $kernel = $this->bootKernelFromConfig(__DIR__, 'testDatabaseProviderPrimaryConnection');
        $container = $kernel->getContainer();

        $circuitBreaker = $container->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);

        $provider = $this->getProvider($circuitBreaker);
        $this->assertInstanceOf(DatabaseProvider::class, $provider);

        $pdo = $this->getPrivateProperty($provider, 'pdo');
        $this->assertEquals('sqlite', $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
    }

    public function testDatabaseProviderSecondaryConnection(): void
    {
        $kernel = $this->bootKernelFromConfig(__DIR__, 'testDatabaseProviderSecondaryConnection');
        $container = $kernel->getContainer();

        $circuitBreaker = $container->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);

        $provider = $this->getProvider($circuitBreaker);
        $this->assertInstanceOf(DatabaseProvider::class, $provider);

        $pdo = $this->getPrivateProperty($provider, 'pdo');
        $this->assertEquals('mysql', $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
    }
}
