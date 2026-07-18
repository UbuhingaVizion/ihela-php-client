<?php

declare(strict_types=1);

namespace Ihela\Dto;

use InvalidArgumentException;

use function mb_strlen;
use function preg_match;

class WithdrawalPayload
{
    public readonly string $debitAccount;
    public readonly string $debitAccountHolder;
    public readonly float $amount;
    public readonly string $description;
    public readonly string $externalReference;
    public readonly string $pinCode;

    public function __construct(
        string $debitAccount,
        string $debitAccountHolder,
        float $amount,
        string $description,
        string $externalReference,
        string $pinCode,
    ) {
        $this->validateAccount($debitAccount, 'debit_account');
        $this->validateAccountHolder($debitAccountHolder, 'debit_account_holder');
        $this->validateAmount($amount, 'amount');
        $this->validateDescription($description, 'description');
        $this->validateReference($externalReference, 'external_reference');
        $this->validatePinCode($pinCode, 'pin_code');

        $this->debitAccount = $debitAccount;
        $this->debitAccountHolder = $debitAccountHolder;
        $this->amount = $amount;
        $this->description = $description;
        $this->externalReference = $externalReference;
        $this->pinCode = $pinCode;
    }

    public function toArray(): array
    {
        return [
            'debit_account' => $this->debitAccount,
            'debit_account_holder' => $this->debitAccountHolder,
            'amount' => $this->amount,
            'description' => $this->description,
            'external_reference' => $this->externalReference,
            'pin_code' => $this->pinCode,
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
