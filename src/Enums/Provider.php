<?php

namespace CircuitBreakerBundle\Enums;

enum Provider: string
{
    case Redis = 'redis';
    case Memcached = 'memcached';
    case Database = 'database';
    case Memory = 'memory';
}
