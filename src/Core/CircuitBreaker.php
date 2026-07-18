<?php

declare(strict_types=1);

namespace Ihela\Core;

use Ihela\Core\Exception\CircuitOpenException;
use Throwable;

use function microtime;

class CircuitBreaker
{
    private int $failureThreshold;
    private float $cooldownSeconds;
    private int $failureCount = 0;
    private ?float $openUntil = null;

    public function __construct(int $failureThreshold = 5, float $cooldownSeconds = 30.0)
    {
        $this->failureThreshold = $failureThreshold;
        $this->cooldownSeconds = $cooldownSeconds;
    }

    public function call(callable $callback): mixed
    {
        if ($this->isOpen()) {
            throw new CircuitOpenException();
        }

        try {
            $result = $callback();
            $this->reset();

            return $result;
        } catch (Throwable $e) {
            if ($e instanceof CircuitOpenException) {
                throw $e;
            }
            $this->recordFailure();

            throw $e;
        }
    }

    public function isOpen(): bool
    {
        if ($this->openUntil === null) {
            return false;
        }

        if (microtime(true) >= $this->openUntil) {
            $this->openUntil = null;
            $this->failureCount = 0;

            return false;
        }

        return true;
    }

    public function recordFailure(): void
    {
        ++$this->failureCount;

        if ($this->failureCount >= $this->failureThreshold) {
            $this->openUntil = microtime(true) + $this->cooldownSeconds;
        }
    }

    public function reset(): void
    {
        $this->failureCount = 0;
        $this->openUntil = null;
    }

    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    public function getOpenUntil(): ?float
    {
        return $this->openUntil;
    }
}
