<?php

namespace CircuitBreakerBundle\Providers;

use CircuitBreaker\Contracts\ProviderInterface;

interface ProviderFactoryInterface
{
    public function create(array $config): ProviderInterface;
}
