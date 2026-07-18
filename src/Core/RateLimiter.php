<?php

declare(strict_types=1);

namespace Ihela\Core;

use Ihela\Core\Exception\RateLimitException;

use function array_filter;
use function array_values;
use function count;
use function microtime;

class RateLimiter
{
    private int $maxRequests;
    private float $windowSeconds;

    /** @var array<float> */
    private array $timestamps = [];

    public function __construct(int $maxRequests = 0, float $windowSeconds = 1.0)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function acquire(): bool
    {
        if ($this->maxRequests <= 0) {
            return true;
        }

        $now = microtime(true);
        $cutoff = $now - $this->windowSeconds;

        $this->timestamps = array_values(
            array_filter($this->timestamps, fn (float $ts) => $ts > $cutoff)
        );

        if (count($this->timestamps) >= $this->maxRequests) {
            return false;
        }

        $this->timestamps[] = $now;

        return true;
    }

    public function checkAndAcquire(): void
    {
        if (!$this->acquire()) {
            throw new RateLimitException();
        }
    }

    public function getRequestCount(): int
    {
        return count($this->timestamps);
    }
}
