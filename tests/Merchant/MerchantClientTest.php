<?php

declare(strict_types=1);

namespace Ihela\Tests\Merchant;

use Ihela\Core\Exception\ApiException;
use Ihela\Core\Exception\AuthenticationException;
use Ihela\Merchant\MerchantClient;
use Ihela\Tests\TestCase;

class MerchantClientTest extends TestCase
{
    public function testConstructAuthenticatesAutomatically(): void
    {
        $this->setupClient();
        $client = new MerchantClient('id', 'secret', '1234');

        $this->assertTrue($client->isAuthenticated());
        $this->assertSame('test-access-token', $client->getAccessToken());
    }

    public function testConstructWithoutAutoAuth(): void
    {
        $client = new MerchantClient('id', 'secret', '1234', autoAuth: false);

        $this->assertFalse($client->isAuthenticated());
        $this->assertNull($client->getAccessToken());
    }

    public function testAuthenticationFailure(): void
    {
        $this->mockHttp([
            ['status' => 401, 'body' => ['code' => '01', 'message' => 'Unauthorized']],
        ]);

        $this->expectException(AuthenticationException::class);
        new MerchantClient('id', 'secret', '1234');
    }

    public function testInitBill(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'bill' => ['code' => 'BILL-001', 'amount' => '2000'],
            ]),
        ]);

        $result = $client->initBill(2000, 'user@test.com', 'Payment', 'REF-001');

        $this->assertArrayHasKey('bill', $result);
        $this->assertSame('BILL-001', $result['bill']['code']);
    }

    public function testInitBillWithBank(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'bill' => ['code' => 'BILL-002'],
            ]),
        ]);

        $result = $client->initBill(5000, 'user@test.com', 'Payment', 'REF-002', bank: 'MOB-0003');

        $this->assertArrayHasKey('bill', $result);
    }

    public function testVerifyBill(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'status' => 'Paid',
                'reference' => 'BILL-001',
            ]),
        ]);

        $result = $client->verifyBill('BILL-001', 'REF-001');

        $this->assertSame('Paid', $result['status']);
    }

    public function testCashinClient(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'success' => true,
                'response_data' => ['reference' => 'CASHIN-001'],
            ]),
        ]);

        $result = $client->cashinClient('MOB-0003', '76077736', 1000, 'REF-001', 'Cashin test');

        $this->assertTrue($result['success']);
    }

    public function testGetBankList(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'objects' => [['slug' => 'MOB-0003', 'name' => 'Mobile Bank']],
                'count' => 1,
            ]),
        ]);

        $result = $client->getBankList();

        $this->assertArrayHasKey('objects', $result);
        $this->assertCount(1, $result['objects']);
    }

    public function testGetCashinBankList(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'objects' => [['slug' => 'MOB-0003']],
            ]),
        ]);

        $result = $client->getCashinBankList();

        $this->assertArrayHasKey('objects', $result);
    }

    public function testGetCashoutBankList(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'objects' => [['slug' => 'MOB-0003']],
            ]),
        ]);

        $result = $client->getCashoutBankList();

        $this->assertArrayHasKey('objects', $result);
    }

    public function testCustomerLookup(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'account_number' => '000001-01',
                'customer_id' => '16',
                'name' => 'John Doe',
            ]),
        ]);

        $result = $client->customerLookup('MOB-0003', accountNumber: '000001-01');

        $this->assertSame('000001-01', $result['account_number']);
        $this->assertSame('John Doe', $result['name']);
    }

    public function testGetUserInfo(): void
    {
        $client = $this->createClient(extraResponses: [
            $this->successResponse([
                'id' => 1,
                'title' => 'Test Merchant',
            ]),
        ]);

        $result = $client->getUserInfo();

        $this->assertSame('Test Merchant', $result['title']);
    }

    public function testClearToken(): void
    {
        $this->setupClient();
        $client = new MerchantClient('id', 'secret', '1234');
        $client->clearToken();

        $this->assertFalse($client->isAuthenticated());
        $this->assertNull($client->getAccessToken());
    }

    public function testApiExceptionOnError(): void
    {
        $this->mockHttp([
            $this->tokenResponse(),
            $this->errorResponse(400, '05'),
        ]);
        $client = new MerchantClient('id', 'secret', '1234');

        $this->expectException(ApiException::class);
        $client->initBill(2000, 'user@test.com', 'Payment', 'REF-001');
    }

    public function testNoAuthTokenReturnsError(): void
    {
        $this->setupClient();
        $client = new MerchantClient('id', 'secret', '1234');
        $client->clearToken();

        $this->mockHttp([]);

        $this->expectException(AuthenticationException::class);
        $client->initBill(2000, 'user@test.com', 'Payment', 'REF-001');
    }

    private function createClient(bool $autoAuth = true, array $extraResponses = []): MerchantClient
    {
        if ($autoAuth) {
            $this->setupClient($extraResponses);
        }

        return new MerchantClient(
            clientId: 'test-client-id',
            clientSecret: 'test-client-secret',
            pinCode: '1234',
            autoAuth: $autoAuth,
        );
    }
}
