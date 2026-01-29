<?php

namespace Tests\Unit\Provider;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\Provider\DatabaseProvider;
use CircuitBreaker\Provider\MemcachedProvider;
use CircuitBreaker\Provider\MemoryProvider;
use CircuitBreaker\Provider\RedisProvider;
use Nyholm\BundleTest\TestKernel;
use Tests\KernelTestCase;

class ProviderTest extends KernelTestCase
{
    public function testMemoryProvider(): void
    {
        $kernel = self::bootKernel(['config' => static function (TestKernel $kernel) {
            $kernel->addTestConfig(__DIR__ . '/config/testMemoryProvider.config.yaml');
        }]);

        $container = $kernel->getContainer();

        $this->assertTrue($container->has('circuit_breaker.provider'));
        $this->assertInstanceOf(MemoryProvider::class, $container->get('circuit_breaker.provider'));

        $this->assertTrue($container->has('circuit_breaker.default'));

        $circuitBreaker = $container->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);
        $this->assertInstanceOf(MemoryProvider::class, $this->getProvider($circuitBreaker));
    }

    public function testRedisProvider(): void
    {
        $kernel = self::bootKernel(['config' => static function (TestKernel $kernel) {
            $kernel->addTestConfig(__DIR__ . '/config/testRedisProvider.config.yaml');
        }]);

        $container = $kernel->getContainer();

        $this->assertTrue($container->has('circuit_breaker.provider'));
        $this->assertInstanceOf(RedisProvider::class, $container->get('circuit_breaker.provider'));

        $this->assertTrue($container->has('circuit_breaker.default'));

        $circuitBreaker = $container->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);
        $this->assertInstanceOf(RedisProvider::class, $this->getProvider($circuitBreaker));
    }

    public function testMemcachedProvider(): void
    {
        $kernel = self::bootKernel(['config' => static function (TestKernel $kernel) {
            $kernel->addTestConfig(__DIR__ . '/config/testMemcachedProvider.config.yaml');
        }]);

        $container = $kernel->getContainer();

        $this->assertTrue($container->has('circuit_breaker.provider'));
        $this->assertInstanceOf(MemcachedProvider::class, $container->get('circuit_breaker.provider'));

        $circuitBreaker = $container->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);
        $this->assertInstanceOf(MemcachedProvider::class, $this->getProvider($circuitBreaker));
    }

    public function testImplicitlyDefaultDatabaseProvider(): void
    {
        $kernel = self::bootKernel(['config' => static function (TestKernel $kernel) {
            $kernel->addTestConfig(__DIR__ . '/config/testImplicitlyDefaultDatabaseProvider.config.yaml');
        }]);

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
        $kernel = self::bootKernel(['config' => static function (TestKernel $kernel) {
            $kernel->addTestConfig(__DIR__ . '/config/testDatabaseProviderPrimaryConnection.config.yaml');
        }]);

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
        $kernel = self::bootKernel(['config' => static function (TestKernel $kernel) {
            $kernel->addTestConfig(__DIR__ . '/config/testDatabaseProviderSecondaryConnection.config.yaml');
        }]);

        $container = $kernel->getContainer();

        $circuitBreaker = $container->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);

        $provider = $this->getProvider($circuitBreaker);
        $this->assertInstanceOf(DatabaseProvider::class, $provider);

        $pdo = $this->getPrivateProperty($provider, 'pdo');
        $this->assertEquals('mysql', $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
    }
}
