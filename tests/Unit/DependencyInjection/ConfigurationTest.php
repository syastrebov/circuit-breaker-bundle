<?php

namespace Tests\Unit\DependencyInjection;

use CircuitBreakerBundle\DependencyInjection\Configuration;
use CircuitBreakerBundle\Enums\Provider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    #[DataProvider('providers')]
    public function testDriverConfiguration(string $value, Provider $enum): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $configs = [
            'circuit_breaker' => [
                'provider' => $value,
            ],
        ];

        $processedConfig = $processor->processConfiguration($configuration, $configs);

        $expected = [
            'provider' => $enum,
            'configurations' => [],
        ];

        $this->assertEquals($expected, $processedConfig);
    }

    public function testRedisConnection(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $configs = [
            'circuit_breaker' => [
                'provider' => 'redis',
                'connections' => [
                    'redis' => [
                        'connection' => 'default',
                    ],
                ],
            ],
        ];

        $processedConfig = $processor->processConfiguration($configuration, $configs);

        $expected = [
            'provider' => Provider::Redis,
            'connections' => [
                'redis' => [
                    'connection' => 'default',
                ],
            ],
            'configurations' => [],
        ];

        $this->assertEquals($expected, $processedConfig);
    }

    public function testMemcachedConnection(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $configs = [
            'circuit_breaker' => [
                'provider' => 'memcached',
                'connections' => [
                    'memcached' => [
                        'servers' => [
                            [
                                'host' => 'memcached-1',
                            ],
                            [
                                'host' => 'memcached-2',
                                'port' => 11211,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $processedConfig = $processor->processConfiguration($configuration, $configs);

        $expected = [
            'provider' => Provider::Memcached,
            'connections' => [
                'memcached' => [
                    'servers' => [
                        [
                            'host' => 'memcached-1',
                        ],
                        [
                            'host' => 'memcached-2',
                            'port' => 11211,
                        ],
                    ],
                ],
            ],
            'configurations' => [],
        ];

        $this->assertEquals($expected, $processedConfig);
    }

    public function testDatabaseConnection(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $configs = [
            'circuit_breaker' => [
                'provider' => 'database',
                'connections' => [
                    'database' => [
                        'connection' => 'default',
                        'table' => 'circuit_breaker',
                    ],
                ],
            ],
        ];

        $processedConfig = $processor->processConfiguration($configuration, $configs);

        $expected = [
            'provider' => Provider::Database,
            'connections' => [
                'database' => [
                    'connection' => 'default',
                    'table' => 'circuit_breaker',
                ],
            ],
            'configurations' => [],
        ];

        $this->assertEquals($expected, $processedConfig);
    }

    public function testDefaultConfiguration(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $configs = [
            'circuit_breaker' => [
                'provider' => 'redis',
                'configurations' => [
                    'default' => [],
                ],
            ],
        ];

        $processedConfig = $processor->processConfiguration($configuration, $configs);

        $expected = [
            'provider' => Provider::Redis,
            'configurations' => [
                'default' => [
                    'retries' => 3,
                    'closed_threshold' => 3,
                    'half_open_threshold' => 3,
                    'retry_interval' => 1000,
                    'open_timeout' => 60,
                    'fallback_or_null' => false,
                ],
            ],
        ];

        $this->assertEquals($expected, $processedConfig);
    }

    public function testConfigurations(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $configs = [
            'circuit_breaker' => [
                'provider' => 'redis',
                'configurations' => [
                    'default' => [
                        'retries' => 3,
                        'closed_threshold' => 3,
                        'half_open_threshold' => 3,
                        'retry_interval' => 1000,
                        'open_timeout' => 60,
                        'fallback_or_null' => false,
                    ],
                    'custom' => [
                        'retries' => 5,
                        'closed_threshold' => 5,
                        'half_open_threshold' => 5,
                        'retry_interval' => 2000,
                        'open_timeout' => 120,
                        'fallback_or_null' => true,
                    ],
                ],
            ],
        ];

        $processedConfig = $processor->processConfiguration($configuration, $configs);

        $expected = [
            'provider' => Provider::Redis,
            'configurations' => [
                'default' => [
                    'retries' => 3,
                    'closed_threshold' => 3,
                    'half_open_threshold' => 3,
                    'retry_interval' => 1000,
                    'open_timeout' => 60,
                    'fallback_or_null' => false,
                ],
                'custom' => [
                    'retries' => 5,
                    'closed_threshold' => 5,
                    'half_open_threshold' => 5,
                    'retry_interval' => 2000,
                    'open_timeout' => 120,
                    'fallback_or_null' => true,
                ],
            ],
        ];

        $this->assertEquals($expected, $processedConfig);
    }

    public static function providers(): array
    {
        return [
            ['redis', Provider::Redis],
            ['memcached', Provider::Memcached],
            ['database', Provider::Database],
            ['memory', Provider::Memory],
        ];
    }
}
