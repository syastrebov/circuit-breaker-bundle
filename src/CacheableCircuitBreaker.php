<?php

namespace CircuitBreakerBundle;

use CircuitBreaker\CircuitBreakerConfig;
use CircuitBreaker\Contracts\CircuitBreakerInterface;
use CircuitBreaker\Enums\CircuitBreakerState;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\CacheItem;

final readonly class CacheableCircuitBreaker implements CircuitBreakerInterface
{
    public function __construct(
        private CircuitBreakerInterface $circuitBreaker,
        private CacheItemPoolInterface $cache,
        private ?LoggerInterface $logger = null
    ) {
    }

    #[\Override]
    public function getConfig(): CircuitBreakerConfig
    {
        return $this->circuitBreaker->getConfig();
    }

    #[\Override]
    public function getState(string $name): CircuitBreakerState
    {
        return $this->circuitBreaker->getState($name);
    }

    #[\Override]
    public function getStateTimestamp(string $name): int
    {
        return $this->circuitBreaker->getStateTimestamp($name);
    }

    #[\Override]
    public function getFailedAttempts(string $name): int
    {
        return $this->circuitBreaker->getFailedAttempts($name);
    }

    #[\Override]
    public function getHalfOpenAttempts(string $name): int
    {
        return $this->circuitBreaker->getHalfOpenAttempts($name);
    }

    #[\Override]
    public function run(string $name, callable $action, ?callable $fallback = null): mixed
    {
        $cacheKey = $this->buildCacheKey($name);

        return $this->circuitBreaker->run(
            $name,
            function () use ($cacheKey, $action): mixed {
                $response = $action();

                try {
                    $cacheItem = $this->cache->getItem($cacheKey);
                    if (!$cacheItem->isHit()) {
                        $cacheItem->set($response);
                        $this->cache->save($cacheItem);
                    }
                } catch (\Throwable $e) {
                    $this->logger?->error('CacheableCircuitBreaker: ' . $e->getMessage());
                }

                return $response;
            },
            function () use ($cacheKey, $fallback): mixed {
                try {
                    $cacheItem = $this->cache->getItem($cacheKey);
                    if ($cacheItem->isHit()) {
                        return $cacheItem->get();
                    }
                } catch (\Throwable $e) {
                    $this->logger?->error('CacheableCircuitBreaker: ' . $e->getMessage());
                }

                if ($fallback !== null) {
                    return $fallback();
                }

                return null;
            }
        );
    }

    protected function buildCacheKey(string $name): string
    {
        $cacheKey = "circuit.{$this->circuitBreaker->getConfig()->prefix}.{$name}.response";

        return str_replace(str_split(CacheItem::RESERVED_CHARACTERS), '.', $cacheKey);
    }
}
