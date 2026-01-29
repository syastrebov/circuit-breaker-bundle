Symfony package for https://github.com/syastrebov/circuit-breaker

## Install

~~~bash
composer require syastrebov/circuit-breaker-bundle
~~~

## Config

config/packages/doctrine.yaml

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

config/packages/circuit_breaker.yaml

~~~yaml
circuit_breaker:
  driver: database
  connections:
    database:
      connection: secondary
  configurations:
    default:
      retries: 3
      closed_threshold: 3
      half_open_threshold: 3
      retry_interval: 1000
      open_timeout: 60
      fallback_or_null: false
    api:
      retries: 3
      closed_threshold: 3
      half_open_threshold: 3
      retry_interval: 1000
      open_timeout: 60
      fallback_or_null: false
~~~

## Usage

### Simple example:

~~~php
use CircuitBreaker\CircuitBreaker;

public function request(CircuitBreaker $circuitBreaker): string
{
    try {
        return $circuitBreaker->run('test', function () {
            return '{"response": "data"}';
        });
    } catch (UnableToProcessException $e) {
        // handle exception
    }
}
~~~

### Use custom config:

~~~php
use CircuitBreaker\CircuitBreaker;

public function request(
    #[Autowire(service: 'circuit_breaker.api')] 
    CircuitBreaker $circuitBreaker
): string 
{
    try {
        return $circuitBreaker->run('test', function () {
            return '{"response": "data"}';
        });
    } catch (UnableToProcessException $e) {
        // handle exception
    }
}
~~~
