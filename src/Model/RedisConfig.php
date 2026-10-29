<?php

namespace CircuitBreakerBundle\Model;

final readonly class RedisConfig
{
    public function __construct(
        private array $config = []
    ) {
    }

    public function getDsn(): ?string
    {
        return $this->config['dsn'] ?? null;
    }

    public function getHost(): string
    {
        return $this->config['host'] ?? '127.0.0.1';
    }

    public function getPort(): int
    {
        return $this->config['port'] ?? 6379;
    }

    /**
     * @return string[]
     */
    public function getNodes(): array
    {
        return array_map(
            fn (array $node) => $node['host'] . ':' . ($node['port'] ?? 6379),
            $this->config['nodes'] ?? []
        );
    }

    public function isPersistent(): bool
    {
        return (bool) ($this->config['persistent'] ?? null);
    }

    public function getPersistentId(): ?int
    {
        if (!$this->isPersistent()) {
            return null;
        }

        return $this->config['persistent_id'] ?? null;
    }

    public function getTimeout(): float
    {
        return $this->config['timeout'] ?? 0.0;
    }

    public function getReadTimeout(): float
    {
        return $this->config['read_timeout'] ?? 0.0;
    }

    public function getRetryInterval(): int
    {
        return $this->config['retry_interval'] ?? 0;
    }

    public function getUsername(): ?string
    {
        return $this->config['username'] ?? null;
    }

    public function getPassword(): ?string
    {
        return $this->config['password'] ?? null;
    }

    public function getPrefix(): ?string
    {
        return $this->config['prefix'] ?? null;
    }

    public function getDatabase(): ?int
    {
        return $this->config['database'] ?? null;
    }

    public function getMaxRetries(): ?int
    {
        return $this->config['max_retries'] ?? null;
    }

    public function getContext(): ?array
    {
        return $this->config['context'] ?? null;
    }
}
