<?php

namespace CircuitBreakerBundle;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\Contracts\CircuitBreakerInterface;
use CircuitBreakerBundle\DependencyInjection\Configuration;
use CircuitBreakerBundle\Enums\Provider;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Predis\Client;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class CircuitBreakerBundle extends AbstractBundle
{
    #[\Override]
    public function configure(DefinitionConfigurator $definition): void
    {
        Configuration::configure($definition);
    }

    #[\Override]
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // if no configurations defined use default one
        $config['connections'] ??= [];
        if (empty($config['configurations'])) {
            $config['configurations']['default'] = [];
        }

        $loader = new PhpFileLoader($builder, new FileLocator(__DIR__ . '/../config'));
        $loader->load('services.php');

        $this->loadProvider($config, $container);
        $this->loadConfigurations($config, $container);
        $this->loadServices($config, $container);
        $this->loadSchemaFilter($container, $builder);
    }

    private function loadProvider(array $config, ContainerConfigurator $container): void
    {
        /** @var Provider $provider */
        $provider = $config['provider'];
        $services = $container->services();

        $services->alias('circuit_breaker.provider', call_user_func(function () use ($provider): string {
            $suffix = $provider->value;
            if ($provider === Provider::Redis && !extension_loaded('redis')) {
                if (class_exists(Client::class)) {
                    $suffix = Provider::Predis->value;
                } else {
                    throw new \Exception('Redis is not installed.');
                }
            }

            return 'circuit_breaker.provider.' . $suffix;
        }))->public();

        // redis
        $services->get('circuit_breaker.driver.redis_factory')->args([
            $config['connections']['redis'] ?? [],
        ]);

        $services->get('circuit_breaker.driver.predis_factory')->args([
            $config['connections']['redis'] ?? [],
        ]);

        // memcached
        $services->get('circuit_breaker.driver.memcached_factory')->args([
            $config['connections']['memcached']['servers'] ?? [],
        ]);

        // database
        $connection = $config['connections']['database']['connection'] ?? 'default';

        $container->parameters()->set(
            'circuit_breaker.driver.database.table',
            $config['connections']['database']['table'] ?? 'circuit_breaker'
        );

        $services->get('circuit_breaker.driver.database')->factory([
            service("doctrine.dbal.{$connection}_connection"),
            'getNativeConnection'
        ]);
    }

    private function loadConfigurations(array $config, ContainerConfigurator $container): void
    {
        /** @var array $config */
        foreach ($config['configurations'] as $name => $config) {
            $container
                ->services()
                ->set("circuit_breaker.$name.config")
                ->parent('circuit_breaker.config.abstract')
                ->args([[
                    ...$config,
                    'prefix' => $name,
                ]]);
        }
    }

    private function loadServices(array $config, ContainerConfigurator $container): void
    {
        $services = $container->services();

        /** @var string[] $configurations */
        $configurations = array_keys($config['configurations']);
        $cachePool = $config['cache_pool'] ?? null;
        $default = in_array('default', $configurations) ? 'default' : $configurations[0];

        if (!empty($config['logger'])) {
            $services->set('circuit_breaker.logger', service($config['logger']));
        }

        foreach ($configurations as $name) {
            $services
                ->set("circuit_breaker.$name")
                ->parent('circuit_breaker.abstract')
                ->arg('$config', service("circuit_breaker.$name.config"));

            if ($cachePool) {
                $services
                    ->set("circuit_breaker.$name.cacheable")
                    ->parent('circuit_breaker.cacheable.abstract')
                    ->arg('$circuitBreaker', service("circuit_breaker.$name"))
                    ->arg('$cache', service($cachePool));
            }
        }

        $services->alias('circuit_breaker', "circuit_breaker.$default")
            ->public();
        $services->alias(CircuitBreaker::class, "circuit_breaker.$default")
            ->public();
        $services->alias(CircuitBreakerInterface::class, "circuit_breaker.$default")
            ->public();

        if ($cachePool) {
            $services->alias('circuit_breaker.cacheable', "circuit_breaker.$default.cacheable")
                ->public();
            $services->alias(CacheableCircuitBreaker::class, "circuit_breaker.$default.cacheable")
                ->public();
        }
    }

    private function loadSchemaFilter(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // add filter to ignore this table
        if ($builder->hasParameter('circuit_breaker.driver.database.table')) {
            $definition = $container
                ->services()
                ->get('circuit_breaker.schema_filter_listener')
                ->args([param('circuit_breaker.driver.database.table')]);

            if ($builder->hasParameter('doctrine.connections')) {
                /** @var array<string, string> $connections */
                $connections = $builder->getParameter('doctrine.connections');
                foreach (array_keys($connections) as $connection) {
                    $definition->tag('doctrine.dbal.schema_filter', [
                        'connection' => $connection,
                    ]);
                }
            }
        }
    }
}
