PHP Circuit Breaker implementation for microservices and API calls.

A Symfony bundle for the https://github.com/syastrebov/circuit-breaker library.

## Install

If you're using flex add to your composer.json

~~~json
{
  ...
  
  "extra": {
    "symfony": {
      "endpoint": [
        "https://api.github.com/repos/syastrebov/recipies/contents/index.json",
        "flex://defaults"
      ]
    }
  }
}
~~~

Install via composer

~~~bash
composer require syastrebov/circuit-breaker-bundle
~~~

## Config

### Supported Providers

#### Database

`config/packages/doctrine.yaml`

~~~yaml
doctrine:
  dbal:
    default_connection: default
    connections:
      default:
        driver: pdo_sqlite
        path: ':memory:'
      secondary:
        driver: pdo_mysql
        host: mysql
        user: user
        password: password
        dbname: database
~~~

`config/packages/circuit_breaker.yaml`

~~~yaml
circuit_breaker:
  driver: database
  connections:
    database:
      connection: secondary
~~~

#### Memcached

`config/packages/circuit_breaker.yaml`

~~~yaml
circuit_breaker:
  driver: memcached
  connections:
    memcached:
      servers:
        # Uses default port
        - host: memcached-1
        # Explicitly define port
        - host: memcached-2
          port: 11211
~~~

#### Redis

Uses `redis` extension or `predis` as fallback.

`config/packages/circuit_breaker.yaml`

~~~yaml
circuit_breaker:
  driver: redis
  connections:
    redis:
      host: redis
      port: 6379
      username: username
      password: password
~~~

#### Redis Cluster

Uses `redis` extension or `predis` as fallback.

`config/packages/circuit_breaker.yaml`

~~~yaml
circuit_breaker:
  driver: redis
  connections:
    redis:
      nodes:
        # Uses default port
        - host: redis-node-1
        - host: redis-node-2
        # Explicitly define port
        - host: redis-node-3
          port: 6379
      password: password
~~~

#### Predis

To force Predis usage despite the redis extension being installed:

`config/packages/circuit_breaker.yaml`

~~~yaml
circuit_breaker:
  driver: predis
~~~

#### Memory

config/packages/circuit_breaker.yaml

~~~yaml
circuit_breaker:
  driver: memory
~~~

### Multiple Instances

`config/packages/circuit_breaker.yaml`

~~~yaml
circuit_breaker:
  configurations:
    default:
      retries: 3
      closed_threshold: 3
      half_open_threshold: 3
      retry_interval: 1000
      open_timeout: 60
      fallback_or_null: false
    api:
      retries: 2
      closed_threshold: 5
      half_open_threshold: 10
      retry_interval: 2000
      open_timeout: 120
      fallback_or_null: true
~~~

~~~php

use CircuitBreaker\Contracts\CircuitBreakerInterface;

public function requestDefault(
    #[Autowire(service: 'circuit_breaker.default')] 
    CircuitBreakerInterface $circuit
): string {
    // handle request
}

public function requestApi(
    #[Autowire(service: 'circuit_breaker.api')] 
    CircuitBreakerInterface $circuit
): string {
    // handle request
}
~~~

### Logger

~~~yaml
circuit_breaker:
  logger: monolog.logger.circuit_breaker
~~~

## Usage

### Simple example:

~~~php
use CircuitBreaker\Contracts\CircuitBreakerInterface;

public function request(CircuitBreakerInterface $circuit): string
{
    try {
        return $circuit->run('test', function () {
            return '{"response": "data"}';
        });
    } catch (UnableToProcessException $e) {
        // handle exception
    }
}
~~~

### Use custom config:

~~~php
use CircuitBreaker\Contracts\CircuitBreakerInterface;

public function request(
    #[Autowire(service: 'circuit_breaker.api')] 
    CircuitBreakerInterface $circuit
): string {
    try {
        return $circuit->run('test', function () {
            return '{"response": "data"}';
        });
    } catch (UnableToProcessException $e) {
        // handle exception
    }
}
~~~

### Stub response:

~~~php
use CircuitBreaker\Contracts\CircuitBreakerInterface;

public function request(
    #[Autowire(service: 'circuit_breaker.api')] 
    CircuitBreakerInterface $circuit
): string {
    return $circuit->run(
        '{endpoint}',
        static function () {
            return (string) (new Client)->get('https://domain/api/{endpoint}')->getBody();
        },
        static function () {
            return json_encode([
                'data' => [
                    'key' => 'default value',
                ],
            ]);
        }
    );
}
~~~

### Cacheable response:

Configure a cache pool to be able to use cacheable circuit breaker.

config/packages/framework.yaml

~~~yaml
framework:
  cache:
    pools:
      circuit_breaker.pool:
        adapter: cache.adapter.filesystem
~~~

config/packages/circuit_breaker.yaml

~~~yaml
circuit_breaker:
  provider: memory
  cache_pool: circuit_breaker.pool

  configurations:
    api:
      retries: 2
      closed_threshold: 5
      half_open_threshold: 10
      retry_interval: 3000
      open_timeout: 120
      fallback_or_null: true

~~~

Use `.cacheable` suffix to use `CacheableCircuitBreaker`.

~~~php
use CircuitBreaker\Contracts\CircuitBreakerInterface;

public function request(
    #[Autowire(service: 'circuit_breaker.api.cacheable')] 
    CircuitBreakerInterface $circuit
): string {
    return $circuit->run('{endpoint}', static function () {
        return (string) (new Client)->get('https://domain/api/{endpoint}')->getBody();
    });
}
~~~
