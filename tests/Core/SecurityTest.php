<?php

declare(strict_types=1);

namespace Ihela\Tests\Core;

use Ihela\Core\Security\DataMasker;
use Ihela\Core\Security\Signature;
use Ihela\Tests\TestCase;

class SecurityTest extends TestCase
{
    public function testGenerateSignature(): void
    {
        $signature = Signature::generate('{"key":"value"}', 'secret-key');
        $this->assertNotEmpty($signature);
    }

    public function testVerifySignatureSuccess(): void
    {
        $payload = '{"amount":1000}';
        $key = 'my-secret-key';
        $signature = Signature::generate($payload, $key);

        $this->assertTrue(Signature::verify($payload, $signature, $key));
    }

    public function testVerifySignatureFailsForWrongKey(): void
    {
        $payload = '{"amount":1000}';
        $signature = Signature::generate($payload, 'key-a');

        $this->assertFalse(Signature::verify($payload, $signature, 'key-b'));
    }

    public function testVerifySignatureFailsForTamperedPayload(): void
    {
        $key = 'my-secret-key';
        $signature = Signature::generate('{"amount":1000}', $key);

        $this->assertFalse(Signature::verify('{"amount":2000}', $signature, $key));
    }

    public function testMaskSensitiveDataPinCode(): void
    {
        $data = ['pin_code' => '1234', 'description' => 'test'];
        $masked = DataMasker::mask($data);

        $this->assertSame('********', $masked['pin_code']);
        $this->assertSame('test', $masked['description']);
    }

    public function testMaskSensitiveDataAccessToken(): void
    {
        $data = ['access_token' => 'secret-token', 'user' => 'john'];
        $masked = DataMasker::mask($data);

        $this->assertSame('********', $masked['access_token']);
        $this->assertSame('john', $masked['user']);
    }

    public function testMaskSensitiveDataRefreshToken(): void
    {
        $data = ['refresh_token' => 'refresh-secret'];
        $masked = DataMasker::mask($data);

        $this->assertSame('********', $masked['refresh_token']);
    }

    public function testMaskSensitiveDataClientSecret(): void
    {
        $data = ['client_secret' => 'super-secret'];
        $masked = DataMasker::mask($data);

        $this->assertSame('********', $masked['client_secret']);
    }

    public function testMaskSensitiveDataClientId(): void
    {
        $data = ['client_id' => 'public-id'];
        $masked = DataMasker::mask($data);

        $this->assertSame('********', $masked['client_id']);
    }

    public function testMaskSensitiveDataPassword(): void
    {
        $data = ['password' => 'mypass'];
        $masked = DataMasker::mask($data);

        $this->assertSame('********', $masked['password']);
    }

    public function testMaskSensitiveDataToken(): void
    {
        $data = ['token' => 'abc123'];
        $masked = DataMasker::mask($data);

        $this->assertSame('********', $masked['token']);
    }

    public function testMaskSensitiveDataNested(): void
    {
        $data = [
            'auth' => ['pin_code' => '5678', 'name' => 'test'],
            'items' => [['secret' => 'hidden'], ['public' => 'visible']],
        ];
        $masked = DataMasker::mask($data);

        $this->assertSame('********', $masked['auth']['pin_code']);
        $this->assertSame('test', $masked['auth']['name']);
        $this->assertSame('********', $masked['items'][0]['secret']);
        $this->assertSame('visible', $masked['items'][1]['public']);
    }

    public function testMaskSensitiveDataNonArray(): void
    {
        $this->assertSame('hello', DataMasker::mask('hello'));
        $this->assertSame(42, DataMasker::mask(42));
    }

    public function testMaskSensitiveDataApiKey(): void
    {
        $data = ['api_key' => 'key-123'];
        $masked = DataMasker::mask($data);
        $this->assertSame('********', $masked['api_key']);
    }

    public function testMaskSensitiveDataAuthorization(): void
    {
        $data = ['authorization' => 'Bearer token'];
        $masked = DataMasker::mask($data);
        $this->assertSame('********', $masked['authorization']);
    }
}
