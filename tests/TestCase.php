<?php

declare(strict_types=1);

namespace Ihela\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Ihela\Core\HttpFactory;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

use function array_map;
use function array_merge;
use function array_replace_recursive;
use function is_array;
use function json_encode;

abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        HttpFactory::reset();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        HttpFactory::reset();
    }

    protected function mockHttp(array $responses): void
    {
        $mockResponses = array_map(function ($response) {
            if (is_array($response)) {
                $status = $response['status'] ?? 200;
                $headers = $response['headers'] ?? ['Content-Type' => 'application/json'];
                $body = json_encode($response['body'] ?? []);

                return new Response($status, $headers, $body);
            }

            return $response;
        }, $responses);

        $mock = new MockHandler($mockResponses);
        $stack = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $stack]);
        HttpFactory::setClient($client);
    }

    protected function tokenResponse(array $overrides = []): array
    {
        return array_replace_recursive([
            'status' => 200,
            'body' => [
                'access_token' => 'test-access-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'scope' => 'read write',
            ],
        ], $overrides);
    }

    protected function tokenResponseWithRefresh(): array
    {
        return [
            'status' => 200,
            'body' => [
                'access_token' => 'test-access-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'refresh_token' => 'refresh-abc123',
                'scope' => 'read write',
            ],
        ];
    }

    protected function successResponse(array $data = []): array
    {
        return [
            'status' => 200,
            'body' => $data + ['response_status' => 200],
        ];
    }

    protected function errorResponse(int $statusCode = 400, ?string $code = '05'): array
    {
        return [
            'status' => $statusCode,
            'body' => [
                'code' => $code,
                'message' => 'An error occurred',
            ],
        ];
    }

    /**
     * Set up mock HTTP for client construction (includes auth token response)
     * and return the full response list so test methods can chain additional responses.
     */
    protected function setupClient(array $extraResponses = []): array
    {
        $responses = array_merge([$this->tokenResponse()], $extraResponses);
        $this->mockHttp($responses);

        return $responses;
    }
}
