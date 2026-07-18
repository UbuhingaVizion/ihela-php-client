<?php

declare(strict_types=1);

namespace Ihela\Tests\Auth;

use Ihela\Auth\MerchantAuthorizationClient;
use Ihela\Core\Exception\ApiException;
use Ihela\Tests\TestCase;

use function strlen;

class MerchantAuthorizationClientTest extends TestCase
{
    public function testConstruct(): void
    {
        $client = new MerchantAuthorizationClient('id', 'secret');
        $this->assertFalse($client->isAuthenticated());
    }

    public function testGetAuthorizationUrl(): void
    {
        $client = new MerchantAuthorizationClient('test-client-id', 'test-secret');

        $url = $client->getAuthorizationUrl('https://app.com/callback/');

        $this->assertStringContainsString('oAuth2/authorize/', $url);
        $this->assertStringContainsString('client_id=test-client-id', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('state=', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
    }

    public function testGetAuthorizationUrlGeneratesState(): void
    {
        $client = new MerchantAuthorizationClient('id', 'secret');
        $url = $client->getAuthorizationUrl('https://app.com/callback/');

        $this->assertNotNull($client->getState());
        $this->assertSame(40, strlen($client->getState()));

        $this->assertStringContainsString('state='.$client->getState(), $url);
    }

    public function testGetAuthorizationUrlWithCustomState(): void
    {
        $client = new MerchantAuthorizationClient('id', 'secret');
        $url = $client->getAuthorizationUrl('https://app.com/callback/', 'my-custom-state');

        $this->assertSame('my-custom-state', $client->getState());
        $this->assertStringContainsString('state=my-custom-state', $url);
    }

    public function testAuthenticate(): void
    {
        $this->mockHttp([
            $this->tokenResponse([
                'body' => [
                    'access_token' => 'auth-code-token',
                    'token_type' => 'Bearer',
                    'refresh_token' => 'refresh-xyz',
                ],
            ]),
            $this->successResponse([
                'id' => 42,
                'email' => 'user@test.com',
            ]),
        ]);

        $client = new MerchantAuthorizationClient('id', 'secret');
        $client->authenticate('auth-code-123', 'https://app.com/callback/');

        $this->assertTrue($client->isAuthenticated());
        $this->assertSame('auth-code-token', $client->getAccessToken());
    }

    public function testAuthenticateFetchesUserInfo(): void
    {
        $this->mockHttp([
            $this->tokenResponse([
                'body' => [
                    'access_token' => 'auth-code-token',
                    'token_type' => 'Bearer',
                ],
            ]),
            $this->successResponse([
                'id' => 42,
                'email' => 'user@test.com',
            ]),
        ]);

        $client = new MerchantAuthorizationClient('id', 'secret');
        $client->authenticate('code', 'https://app.com/callback/');

        $this->assertNotNull($client->userObject);
        $this->assertSame(42, $client->userObject['id']);
    }

    public function testGetUserInfoWhenNotAuthenticated(): void
    {
        $client = new MerchantAuthorizationClient('id', 'secret');
        $this->assertNull($client->getUserInfo());
    }

    public function testClearToken(): void
    {
        $this->mockHttp([
            $this->tokenResponse([
                'body' => [
                    'access_token' => 'token',
                    'token_type' => 'Bearer',
                ],
            ]),
            $this->successResponse(['id' => 1]),
        ]);

        $client = new MerchantAuthorizationClient('id', 'secret');
        $client->authenticate('code', 'https://app.com/callback/');
        $client->clearToken();

        $this->assertFalse($client->isAuthenticated());
        $this->assertNull($client->getAccessToken());
        $this->assertNull($client->userObject);
    }

    public function testBillInit(): void
    {
        $this->mockHttp([
            $this->tokenResponse([
                'body' => [
                    'access_token' => 'token',
                    'token_type' => 'Bearer',
                ],
            ]),
            $this->successResponse(['id' => 1]),
            $this->successResponse([
                'bill' => ['code' => 'BILL-USER-001', 'amount' => '3000'],
            ]),
        ]);

        $client = new MerchantAuthorizationClient('id', 'secret');
        $client->authenticate('code', 'https://app.com/callback/');
        $result = $client->billInit(3000, 'User payment', 'REF-USER-001', 'https://app.com/return/');

        $this->assertArrayHasKey('bill', $result);
        $this->assertSame('BILL-USER-001', $result['bill']['code']);
    }

    public function testBillVerify(): void
    {
        $this->mockHttp([
            $this->tokenResponse([
                'body' => [
                    'access_token' => 'token',
                    'token_type' => 'Bearer',
                ],
            ]),
            $this->successResponse(['id' => 1]),
            $this->successResponse([
                'status' => 'Paid',
                'reference' => 'BILL-USER-001',
            ]),
        ]);

        $client = new MerchantAuthorizationClient('id', 'secret');
        $client->authenticate('code', 'https://app.com/callback/');
        $result = $client->billVerify('BILL-USER-001', 'REF-USER-001');

        $this->assertSame('Paid', $result['status']);
    }

    public function testAuthenticateFailure(): void
    {
        $this->mockHttp([
            ['status' => 400, 'body' => ['code' => '01', 'message' => 'Invalid grant']],
        ]);

        $this->expectException(ApiException::class);
        $client = new MerchantAuthorizationClient('id', 'secret');
        $client->authenticate('bad-code', 'https://app.com/callback/');
    }
}
