<?php

namespace CircuitBreakerBundle\Provider;

abstract class AbstractRedisFactory extends AbstractFactory
{
    protected function getHost(): string
    {
        return $this->config['host'] ?? '127.0.0.1';
    }

    protected function getPort(): int
    {
        return $this->config['port'] ?? 6379;
    }

    /**
     * @return string[]
     */
    protected function getNodes(): array
    {
        return array_map(
            fn (array $node) => $node['host'] . ':' . ($node['port'] ?? 6379),
            $this->config['nodes'] ?? []
        );
    }

    protected function isPersistent(): bool
    {
        return (bool) ($this->config['persistent'] ?? null);
    }

    protected function getPersistentId(): ?int
    {
        if (!$this->isPersistent()) {
            return null;
        }

        return $this->config['persistent_id'] ?? null;
    }

    protected function getTimeout(): float
    {
        return $this->config['timeout'] ?? 0.0;
    }

    protected function getReadTimeout(): float
    {
        return $this->config['read_timeout'] ?? 0.0;
    }

    protected function getRetryInterval(): int
    {
        return $this->config['retry_interval'] ?? 0;
    }

    protected function getUsername(): ?string
    {
        return $this->config['username'] ?? null;
    }

    protected function getPassword(): ?string
    {
        return $this->config['password'] ?? null;
    }

    protected function getPrefix(): ?string
    {
        return $this->config['prefix'] ?? null;
    }

    protected function getDatabase(): ?int
    {
        return $this->config['database'] ?? null;
    }

    protected function getMaxRetries(): ?int
    {
        return $this->config['max_retries'] ?? null;
    }

    protected function getContext(): ?array
    {
        return $this->config['context'] ?? null;
    }
}
