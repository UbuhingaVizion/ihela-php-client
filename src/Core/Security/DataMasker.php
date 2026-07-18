<?php

declare(strict_types=1);

namespace Ihela\Core\Security;

use function in_array;
use function is_array;

class DataMasker
{
    private const SENSITIVE_KEYS = [
        'pin_code',
        'access_token',
        'refresh_token',
        'client_secret',
        'client_id',
        'password',
        'token',
        'secret',
        'api_key',
        'authorization',
    ];

    public static function mask(mixed $data): mixed
    {
        if (is_array($data)) {
            $masked = [];
            foreach ($data as $key => $value) {
                if (in_array($key, self::SENSITIVE_KEYS, true)) {
                    $masked[$key] = '********';
                } else {
                    $masked[$key] = self::mask($value);
                }
            }

            return $masked;
        }

        return $data;
    }
}
