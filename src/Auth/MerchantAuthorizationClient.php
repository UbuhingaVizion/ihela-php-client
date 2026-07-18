<?php

declare(strict_types=1);

namespace Ihela\Auth;

use Ihela\Core\BaseClient;
use Ihela\Core\Exception\ApiException;
use Ihela\Core\Validators;
use Psr\Http\Message\ResponseInterface;

use function array_filter;
use function array_merge;
use function bin2hex;
use function http_build_query;
use function is_array;
use function json_encode;
use function random_bytes;

class MerchantAuthorizationClient extends BaseClient
{
    private const TOKEN_URL = 'oAuth2/token/';
    private const AUTH_URL = 'oAuth2/authorize/';
    private const USER_AGENT = 'ihela-sdk/1.0.0 (php)';

    private const ENDPOINTS = [
        'USER_INFO' => 'api/v1/connected-user/',
        'BILL_INIT' => 'api/v1/payments/bill-init/',
        'BILL_VERIFY' => 'api/v1/payments/bill-check/',
    ];

    /** @var null|array<string, mixed> */
    public ?array $authTokenObject = null;

    /** @var null|array<string, mixed> */
    public ?array $userObject = null;

    private ?string $redirectUri = null;
    private ?string $state = null;

    public function __construct(
        string $clientId,
        string $clientSecret,
        ?string $state = null,
        bool $prod = false,
        ?string $baseUrl = null,
    ) {
        parent::__construct(
            clientId: $clientId,
            clientSecret: $clientSecret,
            prod: $prod,
            baseUrl: $baseUrl,
        );

        $this->state = $state;
    }

    public function getAuthorizationUrl(string $redirectUri, ?string $state = null): string
    {
        if ($this->redirectUri === null || $this->redirectUri !== $redirectUri) {
            $this->redirectUri = $redirectUri;
        }

        if ($this->state === null) {
            $this->state = bin2hex(random_bytes(20));
        }

        if ($state !== null) {
            $this->state = $state;
        }

        $params = http_build_query([
            'state' => $this->state,
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
        ]);

        return $this->getBaseUrl().self::AUTH_URL.'?'.$params;
    }

    public function authenticate(string $authorizationCode, string $redirectUri): void
    {
        $authData = [
            'grant_type' => 'authorization_code',
            'code' => $authorizationCode,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $redirectUri,
        ];

        $url = $this->getBaseUrl().self::TOKEN_URL;

        try {
            $response = $this->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                    'User-Agent' => self::USER_AGENT,
                ],
                'body' => http_build_query($authData),
            ]);

            $this->authTokenObject = $this->getResponseData($response);
            $this->getUserInfo();
        } catch (ApiException $e) {
            throw $e;
        }
    }

    public function isAuthenticated(): bool
    {
        return is_array($this->authTokenObject)
            && isset($this->authTokenObject['access_token']);
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
        $this->userObject = null;
    }

    public function getUserInfo(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        $response = $this->request('GET', $this->getBaseUrl().self::ENDPOINTS['USER_INFO'], [
            'headers' => array_merge(
                $this->getAuthHeaders(),
                [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => self::USER_AGENT,
                ]
            ),
        ]);

        $this->userObject = $this->getResponseData($response);

        return $this->userObject;
    }

    public function billInit(
        int $amount,
        string $description,
        string $reference,
        string $redirectUri,
        ?string $pinCode = null,
    ): array {
        $billData = array_filter([
            'debit_account' => '',
            'amount' => $amount,
            'description' => $description,
            'merchant_description' => $description,
            'merchant_reference' => $reference,
            'redirect_uri' => $redirectUri,
            'payment_product_id' => null,
            'pin_code' => $pinCode,
        ], fn ($v) => $v !== null);

        return $this->postAuthenticated(self::ENDPOINTS['BILL_INIT'], $billData);
    }

    public function billVerify(
        string $billCode,
        string $merchantReference,
        ?string $pinCode = null,
    ): array {
        return $this->postAuthenticated(self::ENDPOINTS['BILL_VERIFY'], [
            'bill_code' => $billCode,
            'merchant_reference' => $merchantReference,
            'pin_code' => $pinCode,
        ]);
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    private function getAuthHeaders(): array
    {
        if ($this->isAuthenticated() && $this->authTokenObject !== null) {
            $tokenType = $this->authTokenObject['token_type'] ?? 'Bearer';

            return [
                'Authorization' => $tokenType.' '.$this->authTokenObject['access_token'],
            ];
        }

        return [];
    }

    private function getResponseData(ResponseInterface $response): array
    {
        return Validators::validateResponse($response, $this->getLogger());
    }

    private function postAuthenticated(string $path, array $data): array
    {
        $url = $this->getBaseUrl().$path;

        $response = $this->request('POST', $url, [
            'headers' => array_merge(
                $this->getAuthHeaders(),
                [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => self::USER_AGENT,
                ]
            ),
            'body' => json_encode($data),
        ]);

        return $this->getResponseData($response);
    }
}
