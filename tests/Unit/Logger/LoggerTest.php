<?php

namespace Tests\Unit\Logger;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\Exceptions\UnableToProcessException;
use CircuitBreakerBundle\CacheableCircuitBreaker;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\KernelTestCase;

class LoggerTest extends KernelTestCase
{
    #[DataProvider('instances')]
    public function testLogger(string $class): void
    {
        $kernel = $this->bootKernelFromConfig(__DIR__, 'logger');
        $container = $kernel->getContainer();

        $circuit = $container->get($class);
        $this->assertInstanceOf($class, $circuit);

        $this->expectException(UnableToProcessException::class);

        $circuit->run('test', function (): void {
            throw new \RuntimeException('Unable to fetch data');
        });

        $logger = $container->get('monolog.logger.circuit_breaker');
        $this->assertInstanceOf(Logger::class, $logger);

        /** @var TestHandler $handler */
        $handler = $logger->getHandlers()[0];
        $records = $handler->getRecords();

        $this->assertCount(2, $records);
        $this->assertEquals('Circuit Breaker: Unable to fetch data', $records[0]);
        $this->assertEquals('Circuit Breaker: Unable to fetch data', $records[1]);
    }

    public static function instances(): array
    {
        return [
            [CircuitBreaker::class],
            [CacheableCircuitBreaker::class],
        ];
    }
}
