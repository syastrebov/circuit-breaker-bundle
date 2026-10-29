<?php

namespace CircuitBreakerBundle\Drivers;

use CircuitBreakerBundle\Model\RedisConfig;
use Predis\Client;
use Predis\ClientInterface;

final class PredisFactory
{
    public static function create(RedisConfig $config): ClientInterface
    {
        if (!interface_exists(ClientInterface::class)) {
            throw new \RuntimeException('Predis is not installed.');
        }

        return !empty($config->getNodes())
            ? self::createCluster($config)
            : self::createSingle($config);
    }

    public static function createSingle(RedisConfig $config): ClientInterface
    {
        $params = [
            'host' => $config->getHost(),
            'port' => $config->getPort(),
            'username' => $config->getUsername(),
            'password' => $config->getPassword(),
            'timeout' => $config->getTimeout(),
            'read_write_timeout' => $config->getReadTimeout(),
        ];

        $predis = new Client($params);
        $predis->connect();

        return $predis;
    }

    public static function createCluster(RedisConfig $config): ClientInterface
    {
        $options = [
            'cluster' => 'redis',
            'parameters' => [
                'password' => $config->getPassword(),
                'retry_interval' => $config->getRetryInterval(),
                'read_write_timeout' => $config->getReadTimeout(),
            ],
        ];

        $predis = new Client($config->getNodes(), $options);
        $predis->connect();

        return $predis;
    }
}
