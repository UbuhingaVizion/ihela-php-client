<?php

declare(strict_types=1);

namespace Ihela\Tests\Core;

use Ihela\Core\CircuitBreaker;
use Ihela\Core\Exception\CircuitOpenException;
use Ihela\Tests\TestCase;
use RuntimeException;

class CircuitBreakerTest extends TestCase
{
    public function testCallSuccessful(): void
    {
        $cb = new CircuitBreaker(3, 5.0);
        $result = $cb->call(fn () => 'success');

        $this->assertSame('success', $result);
        $this->assertSame(0, $cb->getFailureCount());
    }

    public function testCallFailureIncrementsCount(): void
    {
        $cb = new CircuitBreaker(3, 5.0);

        for ($i = 0; $i < 2; ++$i) {
            try {
                $cb->call(fn () => throw new RuntimeException('fail'));
            } catch (RuntimeException $e) {
                // expected
            }
        }

        $this->assertSame(2, $cb->getFailureCount());
        $this->assertFalse($cb->isOpen());
    }

    public function testCallOpensCircuitAfterThreshold(): void
    {
        $cb = new CircuitBreaker(2, 1.0);

        for ($i = 0; $i < 2; ++$i) {
            try {
                $cb->call(fn () => throw new RuntimeException('fail'));
            } catch (RuntimeException $e) {
                // expected
            }
        }

        $this->assertTrue($cb->isOpen());
        $this->assertNotNull($cb->getOpenUntil());
    }

    public function testCallThrowsCircuitOpenWhenOpen(): void
    {
        $cb = new CircuitBreaker(1, 5.0);

        try {
            $cb->call(fn () => throw new RuntimeException('fail'));
        } catch (RuntimeException $e) {
            // expected
        }

        $this->expectException(CircuitOpenException::class);
        $cb->call(fn () => 'should not run');
    }

    public function testCircuitDoesNotOpenUnderThreshold(): void
    {
        $cb = new CircuitBreaker(5, 1.0);

        for ($i = 0; $i < 3; ++$i) {
            try {
                $cb->call(fn () => throw new RuntimeException('fail'));
            } catch (RuntimeException $e) {
                // expected
            }
        }

        $this->assertFalse($cb->isOpen());
    }

    public function testResetClearsFailures(): void
    {
        $cb = new CircuitBreaker(1, 1.0);

        try {
            $cb->call(fn () => throw new RuntimeException('fail'));
        } catch (RuntimeException $e) {
            // expected
        }

        $this->assertTrue($cb->isOpen());
        $cb->reset();

        $this->assertSame(0, $cb->getFailureCount());
        $this->assertNull($cb->getOpenUntil());
        $this->assertFalse($cb->isOpen());
    }
}
