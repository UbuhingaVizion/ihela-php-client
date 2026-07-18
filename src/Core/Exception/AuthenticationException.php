<?php

declare(strict_types=1);

namespace Ihela\Core\Exception;

class AuthenticationException extends IhelaException
{
    public function __construct(string $message = 'Authentication failed. Check your credentials.')
    {
        parent::__construct($message);
    }
}
