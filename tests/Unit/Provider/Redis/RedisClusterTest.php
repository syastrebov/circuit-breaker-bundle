<?php

namespace Tests\Unit\Provider\Redis;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\Enums\CircuitBreakerState;
use CircuitBreaker\Exceptions\UnableToProcessException;
use CircuitBreaker\Providers\RedisProvider;
use Nyholm\BundleTest\TestKernel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\KernelTestCase;

class RedisClusterTest extends RedisTestCase
{
    #[DataProvider('credentialsProvider')]
    public function testRedisClusterProvider(string $name): void
    {
        $kernel = self::bootKernel(['config' => static function (TestKernel $kernel) use ($name) {
            $kernel->addTestConfig(__DIR__ . "/config/$name.config.yaml");
        }]);

        $container = $kernel->getContainer();

        $this->assertTrue($container->has('circuit_breaker.provider'));
        $this->assertInstanceOf(RedisProvider::class, $container->get('circuit_breaker.provider'));

        $circuitBreaker = $container->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);

        $provider = $this->getProvider($circuitBreaker);
        $this->assertInstanceOf(RedisProvider::class, $provider);
        $this->assertInstanceOf(\RedisCluster::class, $this->getRedisClient($provider));


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
            ['testRedisClusterProviderWithPassword'],
            ['testRedisClusterProviderNoPassword'],
        ];
    }
}
