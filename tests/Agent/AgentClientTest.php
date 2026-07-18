<?php

declare(strict_types=1);

namespace Ihela\Tests\Agent;

use Ihela\Agent\AgentClient;
use Ihela\Core\Exception\ApiException;
use Ihela\Tests\TestCase;

class AgentClientTest extends TestCase
{
    public function testConstructAuthenticatesAutomatically(): void
    {
        $this->setupClient();
        $client = new AgentClient('id', 'secret');
        $this->assertTrue($client->isAuthenticated());
    }

    public function testConstructWithoutAutoAuth(): void
    {
        $client = new AgentClient('id', 'secret', autoAuth: false);
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

        $this->assertSame('John Doe', $result['name']);
    }

    public function testAccountBalance(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'balance' => 75000.0,
                'currency' => 'BIF',
            ]),
        ]);
        $result = $client->accountBalance('000001-01');

        $this->assertSame(75000, $result['balance']);
    }

    public function testDeposit(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'success' => true,
                'reference' => 'AG-DEP-001',
            ]),
        ]);
        $result = $client->deposit(
            creditAccount: 'ACC-001',
            creditAccountHolder: 'John Doe',
            amount: 2000.0,
            description: 'Agent deposit',
            externalReference: 'REF-001',
            pinCode: '1234',
        );

        $this->assertTrue($result['success']);
    }

    public function testDepositWithAutoReference(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse(['reference' => 'DEP-002']),
        ]);
        $result = $client->deposit(
            creditAccount: 'ACC-002',
            creditAccountHolder: 'Jane Doe',
            amount: 1500.0,
            description: 'Test',
            pinCode: '5678',
        );

        $this->expectNotToPerformAssertions();
    }

    public function testOperationLookup(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'operation' => 'deposit',
                'details' => ['amount' => 1000],
            ]),
        ]);
        $result = $client->operationLookup('OP-001', '1000');

        $this->assertSame('deposit', $result['operation']);
    }

    public function testValidateWithdrawal(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'success' => true,
                'status' => 'validated',
            ]),
        ]);
        $result = $client->validateWithdrawal(
            externalReference: 'EXT-001',
            pinCode: '4321',
            agentCode: 'AGT-001',
            amount: '5000',
            validationOperationCode: 'OPC',
        );

        $this->assertTrue($result['success']);
    }

    public function testTransactionStatus(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'status' => 'completed',
            ]),
        ]);
        $result = $client->transactionStatus('EXT-001', 'TXN-001');

        $this->assertSame('completed', $result['status']);
    }

    public function testRequestToken(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'access_token' => 'agent-user-token',
                'token_type' => 'Bearer',
            ]),
        ]);
        $result = $client->requestToken('agent-user', 'password');

        $this->assertSame('agent-user-token', $result['access_token']);
    }

    public function testClearToken(): void
    {
        $this->setupClient();
        $client = new AgentClient('id', 'secret');
        $client->clearToken();

        $this->assertFalse($client->isAuthenticated());
    }

    public function testConstructWithToken(): void
    {
        $token = ['access_token' => 'pre-set-agent-token', 'token_type' => 'Bearer'];
        $client = new AgentClient('id', 'secret', token: $token);

        $this->assertSame('pre-set-agent-token', $client->getAccessToken());
    }

    public function testApiExceptionOnError(): void
    {
        $this->mockHttp([
            $this->tokenResponse(),
            $this->errorResponse(400, '05'),
        ]);
        $client = new AgentClient('id', 'secret');

        $this->expectException(ApiException::class);
        $client->accountLookup('000001-01');
    }

    private function createClient(bool $autoAuth = true, array $extraResponses = []): AgentClient
    {
        if ($autoAuth) {
            $this->setupClient($extraResponses);
        }

        return new AgentClient(
            clientId: 'test-client-id',
            clientSecret: 'test-client-secret',
            autoAuth: $autoAuth,
        );
    }
}
