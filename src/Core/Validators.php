<?php

declare(strict_types=1);

namespace Ihela\Core;

use Ihela\Core\Exception\ApiException;
use Ihela\Core\Security\DataMasker;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

use function is_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

class Validators
{
    public static function validateResponse(
        ResponseInterface $response,
        ?LoggerInterface $logger = null,
    ): array {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        $data = self::decodeBody($body);

        if ($statusCode >= 200 && $statusCode < 300) {
            if ($logger !== null) {
                $logger->debug('iHela response', DataMasker::mask($data));
            }

            if (is_array($data)) {
                $data['response_status'] = $statusCode;
            }

            return is_array($data) ? $data : ['data' => $data, 'response_status' => $statusCode];
        }

        if ($logger !== null) {
            $logger->error('iHela API error', [
                'status_code' => $statusCode,
                'body' => DataMasker::mask($data),
            ]);
        }

        throw new ApiException(
            statusCode: $statusCode,
            responseCode: $data['code'] ?? $data['response_code'] ?? null,
            responseData: $data,
        );
    }

    private static function decodeBody(string $body): mixed
    {
        if ($body === '') {
            return null;
        }

        try {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ApiException(
                statusCode: 0,
                responseData: $body,
            );
        }
    }
}
