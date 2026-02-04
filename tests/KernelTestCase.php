<?php

namespace Tests;

use CircuitBreaker\CircuitBreaker;
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

    protected function bootKernelFromConfig(string $dir, string $name): KernelInterface
    {
        return self::bootKernel(['config' => static function (TestKernel $kernel) use ($dir, $name) {
            $kernel->addTestConfig("$dir/config/$name.config.yaml");
        }]);
    }

    protected function getProvider(CircuitBreaker $circuitBreaker): ProviderInterface
    {
        return $this->getPrivateProperty($circuitBreaker, 'provider');
    }

    protected function getPrivateProperty(object $object, string $property): mixed
    {
        $reflectedClass = new \ReflectionClass($object);
        $reflection = $reflectedClass->getProperty($property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
