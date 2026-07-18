<?php

declare(strict_types=1);

namespace Ihela\Core\Exception;

class CircuitOpenException extends IhelaException
{
    public function __construct(string $message = 'Circuit breaker is open. All calls are suspended until the cooldown period expires.')
    {
        parent::__construct($message);
    }
}
