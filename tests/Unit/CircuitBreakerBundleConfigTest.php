<?php

namespace Tests\Unit;

use CircuitBreakerBundle\CircuitBreakerBundle;
use CircuitBreakerBundle\Enums\Provider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

final class CircuitBreakerBundleConfigTest extends TestCase
{
    #[DataProvider('providers')]
    public function testDriverConfiguration(string $value, Provider $enum): void
    {
        $configs = [
            'circuit_breaker' => [
                'provider' => $value,
            ],
        ];

        $expected = [
            'provider' => $enum,
            'configurations' => [],
        ];

        $this->assertProcessedConfig($expected, $configs);
    }

    public function testRedisConnection(): void
    {
        $configs = [
            'circuit_breaker' => [
                'provider' => 'redis',
                'connections' => [
                    'redis' => [
                        'host' => 'redis',
                        'port' => 6379,
                        'timeout' => 1,
                        'persistent_id' => 1,
                        'retry_interval' => 2,
                        'read_timeout' => 3,
                        'max_retries' => 4,
                        'username' => 'user',
                        'password' => 'password',
                        'database' => 1,
                        'prefix' => 'circuit_breaker',
                    ],
                ],
            ],
        ];

        $expected = [
            'provider' => Provider::Redis,
            'connections' => [
                'redis' => [
                    'host' => 'redis',
                    'port' => 6379,
                    'nodes' => [],
                    'timeout' => 1,
                    'persistent_id' => 1,
                    'retry_interval' => 2,
                    'read_timeout' => 3,
                    'max_retries' => 4,
                    'username' => 'user',
                    'password' => 'password',
                    'database' => 1,
                    'prefix' => 'circuit_breaker',
                ],
            ],
            'configurations' => [],
        ];

        $this->assertProcessedConfig($expected, $configs);
    }

    public function testRedisClusterConnection(): void
    {
        $configs = [
            'circuit_breaker' => [
                'provider' => 'redis',
                'connections' => [
                    'redis' => [
                        'nodes' => [
                            [
                                'host' => 'redis-node-1',
                            ],
                            [
                                'host' => 'redis-node-2',
                                'port' => 6379,
                            ]
                        ],
                        'timeout' => 1,
                        'read_timeout' => 3,
                        'password' => 'password',
                        'prefix' => 'circuit_breaker',
                    ],
                ],
            ],
        ];

        $expected = [
            'provider' => Provider::Redis,
            'connections' => [
                'redis' => [
                    'nodes' => [
                        [
                            'host' => 'redis-node-1',
                        ],
                        [
                            'host' => 'redis-node-2',
                            'port' => 6379,
                        ]
                    ],
                    'timeout' => 1,
                    'read_timeout' => 3,
                    'password' => 'password',
                    'prefix' => 'circuit_breaker',
                ],
            ],
            'configurations' => [],
        ];

        $this->assertProcessedConfig($expected, $configs);
    }

    public function testMemcachedConnection(): void
    {
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

        $this->assertProcessedConfig($expected, $configs);
    }

    public function testDatabaseConnection(): void
    {
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

        $this->assertProcessedConfig($expected, $configs);
    }

    public function testDefaultConfiguration(): void
    {
        $configs = [
            'circuit_breaker' => [
                'provider' => 'redis',
                'configurations' => [
                    'default' => [],
                ],
            ],
        ];

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

        $this->assertProcessedConfig($expected, $configs);
    }

    public function testConfigurations(): void
    {
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

        $this->assertProcessedConfig($expected, $configs);
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

    private function assertProcessedConfig(array $expected, array $configs): void
    {
        $bundle = new CircuitBreakerBundle();
        $extension = $bundle->getContainerExtension();
        $configuration = $extension->getConfiguration([], new ContainerBuilder(new ParameterBag()));

        $processor = new Processor();
        $processed = $processor->processConfiguration($configuration, $configs);

        $this->assertEquals($expected, $processed);
    }
}
