<?php

declare(strict_types=1);

namespace Ihela\Tests\Core;

use Ihela\Core\Exception\RateLimitException;
use Ihela\Core\RateLimiter;
use Ihela\Tests\TestCase;

class RateLimiterTest extends TestCase
{
    public function testAcquireWhenNoLimit(): void
    {
        $rl = new RateLimiter(0);
        $this->assertTrue($rl->acquire());
        $this->assertTrue($rl->acquire());
    }

    public function testAcquireWithinLimit(): void
    {
        $rl = new RateLimiter(3, 1.0);
        $this->assertTrue($rl->acquire());
        $this->assertTrue($rl->acquire());
        $this->assertTrue($rl->acquire());
    }

    public function testAcquireExceedsLimit(): void
    {
        $rl = new RateLimiter(2, 10.0);
        $this->assertTrue($rl->acquire());
        $this->assertTrue($rl->acquire());
        $this->assertFalse($rl->acquire());
    }

    public function testCheckAndAcquireThrowsWhenExceeded(): void
    {
        $rl = new RateLimiter(1, 10.0);
        $rl->checkAndAcquire();

        $this->expectException(RateLimitException::class);
        $rl->checkAndAcquire();
    }

    public function testCheckAndAcquireNoThrowWhenNoLimit(): void
    {
        $rl = new RateLimiter(0);

        for ($i = 0; $i < 100; ++$i) {
            $rl->checkAndAcquire();
        }

        $this->expectNotToPerformAssertions();
    }

    public function testGetRequestCount(): void
    {
        $rl = new RateLimiter(5, 10.0);
        $this->assertSame(0, $rl->getRequestCount());

        $rl->acquire();
        $rl->acquire();

        $this->assertSame(2, $rl->getRequestCount());
    }
}
