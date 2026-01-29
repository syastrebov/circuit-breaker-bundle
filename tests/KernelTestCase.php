<?php

namespace Tests;

use CircuitBreaker\CircuitBreaker;
use CircuitBreaker\CircuitBreakerConfig;
use CircuitBreaker\Providers\ProviderInterface;
use CircuitBreakerBundle\CircuitBreakerBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Nyholm\BundleTest\TestKernel;
use Symfony\Component\HttpKernel\KernelInterface;

class KernelTestCase extends \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        $kernel = parent::createKernel($options);
        $kernel->addTestBundle(DoctrineBundle::class);
        $kernel->addTestBundle(CircuitBreakerBundle::class);
        $kernel->handleOptions($options);

        return $kernel;
    }

    protected function getProvider(CircuitBreaker $circuitBreaker): ProviderInterface
    {
        return $this->getPrivateProperty($circuitBreaker, 'provider');
    }

    protected function getConfig(CircuitBreaker $circuitBreaker): CircuitBreakerConfig
    {
        return $this->getPrivateProperty($circuitBreaker, 'config');
    }

    protected function getPrivateProperty(object $object, string $property): mixed
    {
        $reflectedClass = new \ReflectionClass($object);
        $reflection = $reflectedClass->getProperty($property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
