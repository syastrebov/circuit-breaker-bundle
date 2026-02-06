<?php

namespace Tests\Unit\Provider\Predis;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\Enums\CircuitBreakerState;
use CircuitBreaker\Exceptions\UnableToProcessException;
use CircuitBreaker\Providers\PredisProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\KernelTestCase;

final class PredisTest extends KernelTestCase
{
    #[DataProvider('credentialsProvider')]
    public function testPredisCredentials(string $name): void
    {
        $kernel = $this->bootKernelFromConfig(__DIR__, $name);
        $container = $kernel->getContainer();

        $this->assertTrue($container->has('circuit_breaker.provider'));
        $this->assertInstanceOf(PredisProvider::class, $container->get('circuit_breaker.provider'));

        $circuitBreaker = $container->get('circuit_breaker.default');
        $this->assertInstanceOf(CircuitBreaker::class, $circuitBreaker);

        $provider = $this->getProvider($circuitBreaker);
        $this->assertInstanceOf(PredisProvider::class, $provider);

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
            ['testRedisProviderWithUsernameAndPassword'],
            ['testRedisProviderWithOnlyPassword'],
            ['testRedisProviderNoPassword'],
            ['testRedisClusterProviderWithPassword'],
            ['testRedisClusterProviderNoPassword'],
        ];
    }
}
