<?php

declare(strict_types=1);

namespace Ihela\Core;

use Throwable;

use function pow;
use function usleep;

class RetryHandler
{
    private int $maxRetries;
    private float $backoffFactor;
    private float $initialDelayMs;

    public function __construct(
        int $maxRetries = 0,
        float $backoffFactor = 2.0,
        float $initialDelayMs = 500.0,
    ) {
        $this->maxRetries = $maxRetries;
        $this->backoffFactor = $backoffFactor;
        $this->initialDelayMs = $initialDelayMs;
    }

    public function execute(callable $callable): mixed
    {
        if ($this->maxRetries <= 0) {
            return $callable();
        }

        $attempt = 0;

        while (true) {
            try {
                return $callable();
            } catch (Throwable $e) {
                ++$attempt;

                if ($attempt > $this->maxRetries) {
                    throw $e;
                }

                if (!$this->isRetryableError($e)) {
                    throw $e;
                }

                $delay = $this->initialDelayMs * pow($this->backoffFactor, $attempt - 1);
                usleep((int) ($delay * 1000));
            }
        }
    }

    private function isRetryableError(Throwable $e): bool
    {
        if ($e instanceof Exception\ApiException && $e->isRetryable()) {
            return true;
        }

        return false;
    }
}
