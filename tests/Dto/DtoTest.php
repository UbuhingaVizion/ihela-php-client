<?php

declare(strict_types=1);

namespace Ihela\Tests\Dto;

use Ihela\Dto\DepositPayload;
use Ihela\Dto\ValidateWithdrawalPayload;
use Ihela\Dto\WithdrawalPayload;
use Ihela\Tests\TestCase;
use InvalidArgumentException;

class DtoTest extends TestCase
{
    public function testDepositPayloadValid(): void
    {
        $payload = new DepositPayload(
            creditAccount: 'ACC-001',
            creditAccountHolder: 'John Doe',
            amount: 1000.0,
            description: 'Test deposit',
            externalReference: 'REF-001',
            pinCode: '1234',
        );

        $this->assertSame('ACC-001', $payload->creditAccount);
        $this->assertSame(1000.0, $payload->amount);
    }

    public function testDepositPayloadInvalidAccountPattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('credit_account must match pattern');

        new DepositPayload(
            creditAccount: 'acc@bad!',
            creditAccountHolder: 'John',
            amount: 1000.0,
            description: 'Test',
            externalReference: 'REF-001',
            pinCode: '1234',
        );
    }

    public function testDepositPayloadAccountTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('credit_account must be between 3 and 50 characters');

        new DepositPayload(
            creditAccount: 'AB',
            creditAccountHolder: 'John',
            amount: 1000.0,
            description: 'Test',
            externalReference: 'REF-001',
            pinCode: '1234',
        );
    }

    public function testDepositPayloadAmountZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('amount must be greater than 0');

        new DepositPayload(
            creditAccount: 'ACC-001',
            creditAccountHolder: 'John',
            amount: 0.0,
            description: 'Test',
            externalReference: 'REF-001',
            pinCode: '1234',
        );
    }

    public function testDepositPayloadPinCodeNonDigit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('pin_code must contain only digits');

        new DepositPayload(
            creditAccount: 'ACC-001',
            creditAccountHolder: 'John',
            amount: 1000.0,
            description: 'Test',
            externalReference: 'REF-001',
            pinCode: 'AB12',
        );
    }

    public function testDepositPayloadPinCodeTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('pin_code must be between 4 and 6 characters');

        new DepositPayload(
            creditAccount: 'ACC-001',
            creditAccountHolder: 'John',
            amount: 1000.0,
            description: 'Test',
            externalReference: 'REF-001',
            pinCode: '123',
        );
    }

    public function testDepositPayloadToArray(): void
    {
        $payload = new DepositPayload(
            creditAccount: 'ACC-001',
            creditAccountHolder: 'John Doe',
            amount: 1000.0,
            description: 'Test deposit',
            externalReference: 'REF-001',
            pinCode: '1234',
            externalCode: 'EXT-001',
        );

        $array = $payload->toArray();
        $this->assertSame('ACC-001', $array['credit_account']);
        $this->assertSame('John Doe', $array['credit_account_holder']);
        $this->assertSame(1000.0, $array['amount']);
        $this->assertSame('Test deposit', $array['description']);
        $this->assertSame('REF-001', $array['external_reference']);
        $this->assertSame('1234', $array['pin_code']);
        $this->assertSame('EXT-001', $array['external_code']);
    }

    public function testWithdrawalPayloadValid(): void
    {
        $payload = new WithdrawalPayload(
            debitAccount: 'ACC-002',
            debitAccountHolder: 'Jane Doe',
            amount: 500.0,
            description: 'Test withdrawal',
            externalReference: 'REF-002',
            pinCode: '5678',
        );

        $this->assertSame('ACC-002', $payload->debitAccount);
        $this->assertSame(500.0, $payload->amount);
    }

    public function testWithdrawalPayloadInvalidAccountPattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('debit_account must match pattern');

        new WithdrawalPayload(
            debitAccount: 'acc bad',
            debitAccountHolder: 'Jane',
            amount: 500.0,
            description: 'Test',
            externalReference: 'REF-002',
            pinCode: '5678',
        );
    }

    public function testWithdrawalPayloadToArray(): void
    {
        $payload = new WithdrawalPayload(
            debitAccount: 'ACC-002',
            debitAccountHolder: 'Jane Doe',
            amount: 500.0,
            description: 'Test withdrawal',
            externalReference: 'REF-002',
            pinCode: '5678',
        );

        $array = $payload->toArray();
        $this->assertSame('ACC-002', $array['debit_account']);
        $this->assertSame('Jane Doe', $array['debit_account_holder']);
        $this->assertSame(500.0, $array['amount']);
        $this->assertSame('Test withdrawal', $array['description']);
        $this->assertSame('REF-002', $array['external_reference']);
        $this->assertSame('5678', $array['pin_code']);
    }

    public function testValidateWithdrawalPayloadValid(): void
    {
        $payload = new ValidateWithdrawalPayload(
            externalReference: 'REF-003',
            pinCode: '9999',
            agentCode: 'AGT-001',
            amount: '1000',
            validationOperationCode: 'OPC',
        );

        $this->assertSame('REF-003', $payload->externalReference);
        $this->assertSame('AGT-001', $payload->agentCode);
    }

    public function testValidateWithdrawalPayloadReferenceTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('external_reference must be between 3 and 100 characters');

        new ValidateWithdrawalPayload(
            externalReference: 'R',
            pinCode: '9999',
            agentCode: 'AGT',
            amount: '1000',
        );
    }

    public function testValidateWithdrawalPayloadToArray(): void
    {
        $payload = new ValidateWithdrawalPayload(
            externalReference: 'REF-003',
            pinCode: '9999',
            agentCode: 'AGT-001',
            amount: '1000',
            externalCode: 'EXT-003',
            validationOperationCode: 'OP-003',
        );

        $array = $payload->toArray();
        $this->assertSame('REF-003', $array['external_reference']);
        $this->assertSame('9999', $array['pin_code']);
        $this->assertSame('AGT-001', $array['agent_code']);
        $this->assertSame('1000', $array['amount']);
        $this->assertSame('EXT-003', $array['external_code']);
        $this->assertSame('OP-003', $array['validation_operation_code']);
    }
}
