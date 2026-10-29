<?php

namespace CircuitBreakerBundle\Providers;

use CircuitBreaker\Contracts\ProviderInterface;
use CircuitBreaker\Providers\DatabaseProvider;
use Doctrine\Persistence\ConnectionRegistry;

final readonly class DatabaseProviderFactory implements ProviderFactoryInterface
{
    public const string DEFAULT_TABLE = 'circuit_breaker';

    public function __construct(
        private ?ConnectionRegistry $registry = null
    ) {
    }

    public function create(array $config): ProviderInterface
    {
        return new DatabaseProvider(
            $this->getConnection($config),
            $config['table'] ?? self::DEFAULT_TABLE,
        );
    }

    private function getConnection(array $config): \PDO
    {
        if ($this->registry === null) {
            throw new \LogicException(
                'The "database" provider requires doctrine/doctrine-bundle. '
                . 'Run "composer require doctrine/doctrine-bundle".'
            );
        }

        return $this->registry->getConnection($config['connection'] ?? 'default')->getNativeConnection();
    }
}
