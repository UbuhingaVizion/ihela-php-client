<?php

declare(strict_types=1);

namespace Ihela\Core\Exception;

class ApiException extends IhelaException
{
    public readonly ?int $statusCode;
    public readonly ?string $responseCode;
    private mixed $responseData;

    public function __construct(
        string $message = 'An error occurred while communicating with the iHela gateway.',
        ?int $statusCode = null,
        ?string $responseCode = null,
        mixed $responseData = null,
    ) {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->responseCode = $responseCode;
        $this->responseData = $responseData;
    }

    public function isRetryable(): bool
    {
        if ($this->statusCode !== null && $this->statusCode >= 500) {
            return true;
        }
        if ($this->responseCode === '07') {
            return true;
        }

        return false;
    }

    public function getResponseData(): mixed
    {
        return $this->responseData;
    }
}
