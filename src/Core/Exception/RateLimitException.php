<?php

declare(strict_types=1);

namespace Ihela\Core\Exception;

class RateLimitException extends IhelaException
{
    public function __construct(string $message = 'Rate limit exceeded. Too many requests in a short period.')
    {
        parent::__construct($message);
    }
}
