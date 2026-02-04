<?php

namespace Tests\Unit\Provider\Redis;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\Enums\CircuitBreakerState;
use CircuitBreaker\Exceptions\UnableToProcessException;
use CircuitBreaker\Providers\RedisProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\KernelTestCase;

class RedisTest extends KernelTestCase
{
    #[DataProvider('credentialsProvider')]
    public function testRedisCredentials(string $name, string $driverClass): void
    {
        $kernel = $this->bootKernelFromConfig(__DIR__, $name);
        $container = $kernel->getContainer();

        $this->assertTrue($container->has('circuit_breaker.provider'));
        $this->assertInstanceOf(RedisProvider::class, $container->get('circuit_breaker.provider'));

        $circuitBreaker = $container->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);

        $provider = $this->getProvider($circuitBreaker);
        $this->assertInstanceOf(RedisProvider::class, $provider);
        $this->assertInstanceOf($driverClass, $this->getRedisClient($provider));

        $this->expectException(UnableToProcessException::class);

        $circuitBreaker->run($name, static function () {
            throw new \RuntimeException('unable to fetch data');
        });

        $this->assertEquals(CircuitBreakerState::CLOSED, $circuitBreaker->getState($name));
        $this->assertEquals(3, $circuitBreaker->getFailedAttempts($name));
    }

    public static function credentialsProvider(): array
    {
        return [
            ['testRedisProviderWithUsernameAndPassword', \Redis::class],
            ['testRedisProviderWithOnlyPassword', \Redis::class],
            ['testRedisProviderNoPassword', \Redis::class],
            ['testRedisClusterProviderWithPassword', \RedisCluster::class],
            ['testRedisClusterProviderNoPassword', \RedisCluster::class],
        ];
    }

    protected function getRedisClient(RedisProvider $provider): mixed
    {
        return $this->getPrivateProperty($provider, 'redis');
    }
}
