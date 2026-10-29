<?php

namespace CircuitBreakerBundle;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\Contracts\CircuitBreakerInterface;
use CircuitBreaker\Contracts\ProviderInterface;
use CircuitBreaker\Enums\Provider;
use CircuitBreakerBundle\DependencyInjection\Configuration;
use CircuitBreakerBundle\EventListener\SchemaFilterListener;
use CircuitBreakerBundle\Providers\DatabaseProviderFactory;
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
        $this->loadSchemaFilter($config, $container, $builder);
    }

    private function loadProvider(array $config, ContainerConfigurator $container): void
    {
        /** @var Provider $provider */
        $provider = $config['provider'];
        $connection = $config['connections'][$provider->value] ?? [];

        $container->services()
            ->set('circuit_breaker.provider', ProviderInterface::class)
            ->factory([service("circuit_breaker.provider_factory.{$provider->value}"), 'create'])
            ->args([$connection])
            ->public();
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
            $services->alias('circuit_breaker.logger', $config['logger']);
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

    private function loadSchemaFilter(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // add filter to ignore this table
        if (($config['provider'] ?? null) !== Provider::Database) {
            return;
        }

        $table = $config['connections']['database']['table'] ?? DatabaseProviderFactory::DEFAULT_TABLE;

        $definition = $container->services()
            ->set('circuit_breaker.schema_filter_listener', SchemaFilterListener::class)
            ->args([$table])
            ->tag('kernel.event_listener', [
                'event' => 'console.command',
                'method' => 'onConsoleCommand',
            ]);

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
