<?php

declare(strict_types=1);

namespace Ihela\Core;

use Ihela\Core\Exception\AuthenticationException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use function array_merge;
use function http_build_query;
use function is_array;
use function json_encode;

abstract class OAuth2Client extends BaseClient
{
    protected const TOKEN_URL = 'oAuth2/token/';
    protected const USER_AGENT = 'ihela-sdk/1.0.0 (php)';

    /** @var null|array<string, mixed> */
    protected ?array $authTokenObject = null;
    protected ?CircuitBreaker $circuitBreaker = null;
    protected ?RateLimiter $rateLimiter = null;
    protected ?RetryHandler $retryHandler = null;

    public function __construct(
        string $clientId,
        string $clientSecret,
        bool $prod = false,
        ?string $baseUrl = null,
        ?string $signatureKey = null,
        int $rateLimit = 0,
        int $circuitBreakerThreshold = 5,
        float $circuitBreakerCooldown = 30.0,
        int $maxRetries = 0,
    ) {
        parent::__construct(
            clientId: $clientId,
            clientSecret: $clientSecret,
            prod: $prod,
            baseUrl: $baseUrl,
            signatureKey: $signatureKey,
        );

        $this->circuitBreaker = new CircuitBreaker($circuitBreakerThreshold, $circuitBreakerCooldown);
        $this->rateLimiter = new RateLimiter($rateLimit);
        $this->retryHandler = new RetryHandler($maxRetries);
    }

    public function authenticate(): void
    {
        $url = $this->getBaseUrl().self::TOKEN_URL;

        try {
            $response = $this->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                    'User-Agent' => self::USER_AGENT,
                ],
                'body' => http_build_query(['grant_type' => 'client_credentials']),
                'auth' => [$this->clientId, $this->clientSecret],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new AuthenticationException(
                    'Authentication failed with status code '.$response->getStatusCode()
                );
            }

            $this->authTokenObject = $this->getResponseData($response);
        } catch (AuthenticationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new AuthenticationException(
                'Connection to iHela gateway failed: '.$e->getMessage()
            );
        }
    }

    public function isAuthenticated(): bool
    {
        return is_array($this->authTokenObject)
            && isset($this->authTokenObject['access_token']);
    }

    public function ensureAuthenticated(): void
    {
        if (!$this->isAuthenticated()) {
            $this->authenticate();
        }
    }

    public function getAccessToken(): ?string
    {
        if ($this->isAuthenticated() && $this->authTokenObject !== null) {
            return $this->authTokenObject['access_token'];
        }

        return null;
    }

    public function getTokenType(): ?string
    {
        if ($this->isAuthenticated() && $this->authTokenObject !== null) {
            return $this->authTokenObject['token_type'] ?? null;
        }

        return null;
    }

    public function clearToken(): void
    {
        $this->authTokenObject = null;
    }

    public function getCircuitBreaker(): CircuitBreaker
    {
        return $this->circuitBreaker;
    }

    public function getRateLimiter(): RateLimiter
    {
        return $this->rateLimiter;
    }

    public function getTokenObject(): ?array
    {
        return $this->authTokenObject;
    }

    protected function getAuthHeaders(): array
    {
        $this->ensureAuthenticated();

        if ($this->isAuthenticated() && $this->authTokenObject !== null) {
            $tokenType = $this->authTokenObject['token_type'] ?? 'Bearer';

            return [
                'Authorization' => $tokenType.' '.$this->authTokenObject['access_token'],
            ];
        }

        return [];
    }

    protected function getResponseData(ResponseInterface $response): array
    {
        return Validators::validateResponse($response, $this->getLogger());
    }

    protected function get(string $path, array $params = []): array
    {
        $url = $this->getBaseUrl().$path;

        if (!empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        return $this->executeRequest(function () use ($url) {
            $response = $this->request('GET', $url, [
                'headers' => array_merge(
                    $this->getAuthHeaders(),
                    [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'User-Agent' => self::USER_AGENT,
                    ]
                ),
            ]);

            return $this->getResponseData($response);
        });
    }

    protected function post(string $path, array $data = []): array
    {
        $url = $this->getBaseUrl().$path;
        $headers = array_merge(
            $this->getAuthHeaders(),
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => self::USER_AGENT,
            ]
        );

        if ($this->signatureKey !== null) {
            $headers['X-iHela-Signature'] = Security\Signature::generate(
                json_encode($data),
                $this->signatureKey
            );
        }

        return $this->executeRequest(function () use ($url, $headers, $data) {
            $response = $this->request('POST', $url, [
                'headers' => $headers,
                'body' => json_encode($data),
            ]);

            return $this->getResponseData($response);
        });
    }

    protected function executeRequest(callable $callable): array
    {
        $this->rateLimiter->checkAndAcquire();

        return $this->circuitBreaker->call(function () use ($callable) {
            return $this->retryHandler->execute($callable);
        });
    }
}
