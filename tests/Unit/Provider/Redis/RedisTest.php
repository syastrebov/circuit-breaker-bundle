<?php

namespace Tests\Unit\Provider\Redis;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\Providers\RedisProvider;
use Nyholm\BundleTest\TestKernel;
use Tests\KernelTestCase;

class RedisTest extends KernelTestCase
{
    public function testRedisProvider(): void
    {
        $kernel = self::bootKernel(['config' => static function (TestKernel $kernel) {
            $kernel->addTestConfig(__DIR__ . '/config/testRedisProvider.config.yaml');
        }]);

        $container = $kernel->getContainer();

        $this->assertTrue($container->has('circuit_breaker.provider'));
        $this->assertInstanceOf(RedisProvider::class, $container->get('circuit_breaker.provider'));

        $circuitBreaker = $container->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);

        $provider = $this->getProvider($circuitBreaker);
        $this->assertInstanceOf(RedisProvider::class, $provider);
        $this->assertInstanceOf(\Redis::class, $this->getRedisClient($provider));
    }

    public function testRedisClusterProvider(): void
    {
        $kernel = self::bootKernel(['config' => static function (TestKernel $kernel) {
            $kernel->addTestConfig(__DIR__ . '/config/testRedisClusterProvider.config.yaml');
        }]);

        $container = $kernel->getContainer();

        $this->assertTrue($container->has('circuit_breaker.provider'));
        $this->assertInstanceOf(RedisProvider::class, $container->get('circuit_breaker.provider'));

        $circuitBreaker = $container->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);

        $provider = $this->getProvider($circuitBreaker);
        $this->assertInstanceOf(RedisProvider::class, $provider);
        $this->assertInstanceOf(\RedisCluster::class, $this->getRedisClient($provider));
    }

    protected function getRedisClient(RedisProvider $provider): mixed
    {
        return $this->getPrivateProperty($provider, 'redis');
    }
}
