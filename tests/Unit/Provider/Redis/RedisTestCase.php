<?php

namespace Tests\Unit\Provider\Redis;

use CircuitBreaker\Providers\RedisProvider;
use Tests\KernelTestCase;

abstract class RedisTestCase extends KernelTestCase
{
    protected function getRedisClient(RedisProvider $provider): mixed
    {
        return $this->getPrivateProperty($provider, 'redis');
    }
}
