<?php

namespace CircuitBreakerBundle\Provider;

use Predis\Client;

final class PredisFactory extends AbstractRedisFactory
{
    public function create(): Client
    {
        if (!class_exists(Client::class)) {
            throw new \RuntimeException('Predis is not installed.');
        }

        return !empty($this->getNodes())
            ? $this->createCluster()
            : $this->createSingle();
    }

    public function createSingle(): Client
    {
        $params = [
            'host' => $this->getHost(),
            'port' => $this->getPort(),
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
            'timeout' => $this->getTimeout(),
            'read_write_timeout' => $this->getReadTimeout(),
        ];

        $predis = new Client($params);
        $predis->connect();

        return $predis;
    }

    public function createCluster(): Client
    {
        $options = [
            'cluster' => 'redis',
            'parameters' => [
                'password' => $this->getPassword(),
                'retry_interval' => $this->getRetryInterval(),
                'read_write_timeout' => $this->getReadTimeout(),
            ],
        ];

        $predis = new Client($this->getNodes(), $options);
        $predis->connect();

        return $predis;
    }
}
