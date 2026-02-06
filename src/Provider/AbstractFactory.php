<?php

namespace CircuitBreakerBundle\Provider;

abstract class AbstractFactory
{
    public function __construct(
        protected readonly array $config
    ) {
    }
}
