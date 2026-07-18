<?php

declare(strict_types=1);

namespace Ihela\Core\Security;

use function hash_equals;
use function hash_hmac;

class Signature
{
    public static function generate(string $payload, string $secretKey): string
    {
        return hash_hmac('sha256', $payload, $secretKey);
    }

    public static function verify(string $payload, string $signature, string $secretKey): bool
    {
        return hash_equals(
            self::generate($payload, $secretKey),
            $signature
        );
    }
}
