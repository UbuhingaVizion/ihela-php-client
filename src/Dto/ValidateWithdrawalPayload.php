<?php

declare(strict_types=1);

namespace Ihela\Dto;

use InvalidArgumentException;

use function mb_strlen;
use function preg_match;

class ValidateWithdrawalPayload
{
    public readonly string $externalReference;
    public readonly string $pinCode;
    public readonly string $agentCode;
    public readonly string $amount;
    public readonly string $externalCode;
    public readonly string $validationOperationCode;

    public function __construct(
        string $externalReference,
        string $pinCode,
        string $agentCode,
        string $amount,
        string $externalCode = '',
        string $validationOperationCode = '',
    ) {
        $this->validateReference($externalReference, 'external_reference');
        $this->validatePinCode($pinCode, 'pin_code');
        $this->validateAgentCode($agentCode, 'agent_code');
        $this->validateAmount($amount, 'amount');
        $this->validateOperationCode($validationOperationCode, 'validation_operation_code');

        $this->externalReference = $externalReference;
        $this->pinCode = $pinCode;
        $this->agentCode = $agentCode;
        $this->amount = $amount;
        $this->externalCode = $externalCode;
        $this->validationOperationCode = $validationOperationCode;
    }

    public function toArray(): array
    {
        return [
            'external_reference' => $this->externalReference,
            'pin_code' => $this->pinCode,
            'agent_code' => $this->agentCode,
            'amount' => $this->amount,
            'external_code' => $this->externalCode,
            'validation_operation_code' => $this->validationOperationCode,
        ];
    }

    private function validateReference(string $value, string $field): void
    {
        $len = mb_strlen($value);
        if ($len < 3 || $len > 100) {
            throw new InvalidArgumentException("{$field} must be between 3 and 100 characters");
        }
    }

    private function validatePinCode(string $value, string $field): void
    {
        $len = mb_strlen($value);
        if ($len < 4 || $len > 6) {
            throw new InvalidArgumentException("{$field} must be between 4 and 6 characters");
        }
        if (!preg_match('/^\d+$/', $value)) {
            throw new InvalidArgumentException("{$field} must contain only digits");
        }
    }

    private function validateAgentCode(string $value, string $field): void
    {
        $len = mb_strlen($value);
        if ($len < 3 || $len > 50) {
            throw new InvalidArgumentException("{$field} must be between 3 and 50 characters");
        }
    }

    private function validateAmount(string $value, string $field): void
    {
        if (mb_strlen($value) < 1) {
            throw new InvalidArgumentException("{$field} must be at least 1 character");
        }
    }

    private function validateOperationCode(string $value, string $field): void
    {
        $len = mb_strlen($value);
        if ($len < 3) {
            throw new InvalidArgumentException("{$field} must be at least 3 characters");
        }
    }
}
