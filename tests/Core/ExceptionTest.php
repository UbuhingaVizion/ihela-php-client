<?php

declare(strict_types=1);

namespace Ihela\Tests\Core;

use Ihela\Core\Exception\ApiException;
use Ihela\Core\Exception\AuthenticationException;
use Ihela\Core\Exception\CircuitOpenException;
use Ihela\Core\Exception\IhelaException;
use Ihela\Core\Exception\RateLimitException;
use Ihela\Tests\TestCase;

class ExceptionTest extends TestCase
{
    public function testIhelaExceptionIsThrowable(): void
    {
        $this->expectException(IhelaException::class);
        $this->expectExceptionMessage('test');

        throw new IhelaException('test');
    }

    public function testAuthenticationExceptionExtendsBase(): void
    {
        $e = new AuthenticationException();
        $this->assertInstanceOf(IhelaException::class, $e);
        $this->assertStringContainsString('Authentication failed', $e->getMessage());
    }

    public function testAuthenticationExceptionCustomMessage(): void
    {
        $e = new AuthenticationException('Custom auth error');
        $this->assertSame('Custom auth error', $e->getMessage());
    }

    public function testApiExceptionDefaults(): void
    {
        $e = new ApiException();
        $this->assertNull($e->statusCode);
        $this->assertNull($e->responseCode);
        $this->assertFalse($e->isRetryable());
    }

    public function testApiExceptionRetryableStatus500(): void
    {
        $e = new ApiException(statusCode: 500, responseCode: '01');
        $this->assertTrue($e->isRetryable());
    }

    public function testApiExceptionRetryableStatus502(): void
    {
        $e = new ApiException(statusCode: 502, responseCode: '01');
        $this->assertTrue($e->isRetryable());
    }

    public function testApiExceptionRetryableResponseCode07(): void
    {
        $e = new ApiException(statusCode: 400, responseCode: '07');
        $this->assertTrue($e->isRetryable());
    }

    public function testApiExceptionNotRetryableFor400(): void
    {
        $e = new ApiException(statusCode: 400, responseCode: '05');
        $this->assertFalse($e->isRetryable());
    }

    public function testApiExceptionNotRetryableFor404(): void
    {
        $e = new ApiException(statusCode: 404, responseCode: '99');
        $this->assertFalse($e->isRetryable());
    }

    public function testApiExceptionStoresResponseData(): void
    {
        $data = ['code' => '05', 'message' => 'Insufficient funds'];
        $e = new ApiException(responseData: $data);
        $this->assertSame($data, $e->getResponseData());
    }

    public function testRateLimitException(): void
    {
        $e = new RateLimitException();
        $this->assertInstanceOf(IhelaException::class, $e);
        $this->assertStringContainsString('Rate limit exceeded', $e->getMessage());
    }

    public function testCircuitOpenException(): void
    {
        $e = new CircuitOpenException();
        $this->assertInstanceOf(IhelaException::class, $e);
        $this->assertStringContainsString('Circuit breaker is open', $e->getMessage());
    }
}
