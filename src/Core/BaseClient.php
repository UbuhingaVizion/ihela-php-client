<?php

declare(strict_types=1);

namespace Ihela\Core;

use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function bin2hex;
use function parse_url;
use function random_bytes;
use function str_split;
use function vsprintf;

use const PHP_URL_HOST;
use const PHP_URL_SCHEME;

class BaseClient
{
    protected string $clientId;
    protected string $clientSecret;
    protected bool $prodEnv;
    protected ?string $signatureKey;
    protected ?string $baseUrl;

    private ?ClientInterface $http = null;
    private ?LoggerInterface $logger = null;

    public function __construct(
        string $clientId,
        string $clientSecret,
        bool $prod = false,
        ?string $baseUrl = null,
        ?string $signatureKey = null,
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->prodEnv = $prod;
        $this->signatureKey = $signatureKey;
        $this->baseUrl = $baseUrl;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getSignatureKey(): ?string
    {
        return $this->signatureKey;
    }

    public function isProd(): bool
    {
        return $this->prodEnv;
    }

    protected function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    protected function getBaseUrl(): string
    {
        if ($this->baseUrl !== null) {
            $this->validateBaseUrl($this->baseUrl);

            return $this->baseUrl;
        }

        return $this->prodEnv
            ? 'https://gate.ihela.online/'
            : 'https://testgate.ihela.online/';
    }

    protected function getHttp(): ClientInterface
    {
        if ($this->http === null) {
            $this->http = HttpFactory::discover();
        }

        return $this->http;
    }

    protected function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $request = HttpFactory::createRequest($method, $url, $options);

        return $this->getHttp()->sendRequest($request);
    }

    protected function generateReference(): string
    {
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
    }

    private function validateBaseUrl(string $url): void
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if ($scheme === 'http' && $host !== '127.0.0.1' && $host !== 'localhost') {
            throw new InvalidArgumentException(
                'HTTP is only allowed for localhost/127.0.0.1. Use HTTPS for all other hosts.'
            );
        }
    }
}
