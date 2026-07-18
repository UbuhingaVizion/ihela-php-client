<?php

declare(strict_types=1);

namespace Ihela\Dto;

use InvalidArgumentException;

use function mb_strlen;
use function preg_match;

class DepositPayload
{
    public readonly string $creditAccount;
    public readonly string $creditAccountHolder;
    public readonly float $amount;
    public readonly string $description;
    public readonly string $externalReference;
    public readonly string $pinCode;
    public readonly string $externalCode;

    public function __construct(
        string $creditAccount,
        string $creditAccountHolder,
        float $amount,
        string $description,
        string $externalReference,
        string $pinCode,
        string $externalCode = '',
    ) {
        $this->validateAccount($creditAccount, 'credit_account');
        $this->validateAccountHolder($creditAccountHolder, 'credit_account_holder');
        $this->validateAmount($amount, 'amount');
        $this->validateDescription($description, 'description');
        $this->validateReference($externalReference, 'external_reference');
        $this->validatePinCode($pinCode, 'pin_code');

        $this->creditAccount = $creditAccount;
        $this->creditAccountHolder = $creditAccountHolder;
        $this->amount = $amount;
        $this->description = $description;
        $this->externalReference = $externalReference;
        $this->pinCode = $pinCode;
        $this->externalCode = $externalCode;
    }

    public function toArray(): array
    {
        return [
            'credit_account' => $this->creditAccount,
            'credit_account_holder' => $this->creditAccountHolder,
            'amount' => $this->amount,
            'description' => $this->description,
            'external_reference' => $this->externalReference,
            'pin_code' => $this->pinCode,
            'external_code' => $this->externalCode,
        ];
    }

    private function validateAccount(string $value, string $field): void
    {
        $len = mb_strlen($value);
        if ($len < 3 || $len > 50) {
            throw new InvalidArgumentException("{$field} must be between 3 and 50 characters");
        }
        if (!preg_match('/^[A-Z0-9\-]+$/', $value)) {
            throw new InvalidArgumentException("{$field} must match pattern [A-Z0-9\\-]");
        }
    }

    private function validateAccountHolder(string $value, string $field): void
    {
        $len = mb_strlen($value);
        if ($len < 2 || $len > 100) {
            throw new InvalidArgumentException("{$field} must be between 2 and 100 characters");
        }
    }

    private function validateAmount(float $value, string $field): void
    {
        if ($value <= 0) {
            throw new InvalidArgumentException("{$field} must be greater than 0");
        }
    }

    private function validateDescription(string $value, string $field): void
    {
        $len = mb_strlen($value);
        if ($len < 1 || $len > 255) {
            throw new InvalidArgumentException("{$field} must be between 1 and 255 characters");
        }
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
}
