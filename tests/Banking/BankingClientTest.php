<?php

declare(strict_types=1);

namespace Ihela\Tests\Banking;

use Ihela\Banking\BankingClient;
use Ihela\Core\Exception\ApiException;
use Ihela\Core\Exception\AuthenticationException;
use Ihela\Tests\TestCase;
use InvalidArgumentException;

class BankingClientTest extends TestCase
{
    public function testConstructAuthenticatesAutomatically(): void
    {
        $this->setupClient();
        $client = new BankingClient('id', 'secret');
        $this->assertTrue($client->isAuthenticated());
    }

    public function testConstructWithoutAutoAuth(): void
    {
        $client = new BankingClient('id', 'secret', autoAuth: false);
        $this->assertFalse($client->isAuthenticated());
    }

    public function testPing(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse(['status' => 'ok']),
        ]);
        $result = $client->ping();

        $this->assertSame('ok', $result['status']);
    }

    public function testAccountLookup(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'account_number' => '000001-01',
                'name' => 'John Doe',
            ]),
        ]);
        $result = $client->accountLookup('000001-01');

        $this->assertSame('000001-01', $result['account_number']);
    }

    public function testAccountBalance(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'account_number' => '000001-01',
                'balance' => 50000.0,
                'currency' => 'BIF',
            ]),
        ]);
        $result = $client->accountBalance('000001-01');

        $this->assertSame(50000, $result['balance']);
    }

    public function testDeposit(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'success' => true,
                'reference' => 'DEP-001',
            ]),
        ]);
        $result = $client->deposit(
            creditAccount: 'ACC-001',
            creditAccountHolder: 'John Doe',
            amount: 1000.0,
            description: 'Test deposit',
            externalReference: 'REF-001',
            pinCode: '1234',
        );

        $this->assertTrue($result['success']);
    }

    public function testDepositValidatesPayload(): void
    {
        $this->setupClient();
        $client = new BankingClient('id', 'secret');

        $this->expectException(InvalidArgumentException::class);
        $client->deposit(
            creditAccount: 'AB',
            creditAccountHolder: 'J',
            amount: 0.0,
            description: '',
            externalReference: 'R',
            pinCode: '12',
        );
    }

    public function testWithdrawal(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'success' => true,
                'reference' => 'WIT-001',
            ]),
        ]);
        $result = $client->withdrawal(
            debitAccount: 'ACC-001',
            debitAccountHolder: 'John Doe',
            amount: 500.0,
            description: 'Test withdrawal',
            externalReference: 'REF-002',
            pinCode: '5678',
        );

        $this->assertTrue($result['success']);
    }

    public function testWithdrawalValidatesPayload(): void
    {
        $this->setupClient();
        $client = new BankingClient('id', 'secret');

        $this->expectException(InvalidArgumentException::class);
        $client->withdrawal(
            debitAccount: 'bad account!',
            debitAccountHolder: 'John Doe',
            amount: 500.0,
            description: 'Test',
            externalReference: 'REF',
            pinCode: '5678',
        );
    }

    public function testStatement(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'transactions' => [['amount' => 1000, 'type' => 'deposit']],
            ]),
        ]);
        $result = $client->statement('000001-01');

        $this->assertArrayHasKey('transactions', $result);
    }

    public function testTransactionStatus(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'status' => 'completed',
                'reference' => 'TXN-001',
            ]),
        ]);
        $result = $client->transactionStatus('EXT-001', 'TXN-001');

        $this->assertSame('completed', $result['status']);
    }

    public function testTransactionFee(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'fee' => 200,
                'currency' => 'BIF',
            ]),
        ]);
        $result = $client->transactionFee('BIF', 'withdrawal', '5000');

        $this->assertSame(200, $result['fee']);
    }

    public function testRequestToken(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'access_token' => 'user-access-token',
                'token_type' => 'Bearer',
            ]),
        ]);
        $result = $client->requestToken('username', 'password');

        $this->assertSame('user-access-token', $result['access_token']);
        $this->assertSame('user-access-token', $client->getAccessToken());
    }

    public function testRefreshToken(): void
    {
        $this->mockHttp([
            $this->tokenResponseWithRefresh(),
            $this->successResponse(['access' => 'refreshed-token']),
        ]);
        $client = new BankingClient('id', 'secret');
        $client->refreshToken();

        $this->assertSame('refreshed-token', $client->getAccessToken());
    }

    public function testRefreshTokenWithoutTokenFails(): void
    {
        $client = new BankingClient('id', 'secret', autoAuth: false);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('No valid refresh token available');
        $client->refreshToken();
    }

    public function testClearToken(): void
    {
        $this->setupClient();
        $client = new BankingClient('id', 'secret');
        $client->clearToken();

        $this->assertFalse($client->isAuthenticated());
        $this->assertNull($client->getAccessToken());
    }

    public function testConstructWithToken(): void
    {
        $token = ['access_token' => 'pre-set-token', 'token_type' => 'Bearer'];
        $client = new BankingClient('id', 'secret', token: $token);

        $this->assertTrue($client->isAuthenticated());
        $this->assertSame('pre-set-token', $client->getAccessToken());
    }

    public function testApiExceptionOnError(): void
    {
        $this->mockHttp([
            $this->tokenResponse(),
            $this->errorResponse(400, '05'),
        ]);
        $client = new BankingClient('id', 'secret');

        $this->expectException(ApiException::class);
        $client->accountLookup('000001-01');
    }

    private function createClient(bool $autoAuth = true, array $extraResponses = []): BankingClient
    {
        if ($autoAuth) {
            $this->setupClient($extraResponses);
        }

        return new BankingClient(
            clientId: 'test-client-id',
            clientSecret: 'test-client-secret',
            autoAuth: $autoAuth,
        );
    }
}
