<?php

namespace CircuitBreakerBundle\Providers;

use CircuitBreaker\Contracts\ProviderInterface;
use CircuitBreaker\Providers\MemoryProvider;

final readonly class MemoryProviderFactory implements ProviderFactoryInterface
{
    public function create(array $config): ProviderInterface
    {
        return new MemoryProvider();
    }
}
