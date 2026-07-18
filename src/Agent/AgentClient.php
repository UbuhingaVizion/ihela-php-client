<?php

declare(strict_types=1);

namespace Ihela\Agent;

use Ihela\Core\Exception\AuthenticationException;
use Ihela\Core\OAuth2Client;
use Ihela\Dto\DepositPayload;
use Ihela\Dto\ValidateWithdrawalPayload;

class AgentClient extends OAuth2Client
{
    private const ENDPOINTS = [
        'PING' => 'ihela/api/v1/ping/',
        'REFRESH' => 'ihela/api/v1/auth-token/refresh/',
        'AUTH_TOKEN' => 'ihela/api/v1/auth-token/',
        'LOOKUP' => 'ihela/api/v1/account-lookup/',
        'BALANCE' => 'ihela/api/v1/bsces/balance/',
        'DEPOSIT' => 'ihela/api/v1/agent-deposit/',
        'OPERATION_LOOKUP' => 'ihela/api/v1/operation-lookup/',
        'VALIDATE_WITHDRAWAL' => 'ihela/api/v1/validate-withdrawal/',
        'STATUS' => 'ihela/api/v1/transaction-status/',
    ];

    public function __construct(
        string $clientId,
        string $clientSecret,
        bool $prod = false,
        ?string $baseUrl = null,
        ?string $signatureKey = null,
        ?array $token = null,
        bool $autoAuth = true,
        int $rateLimit = 0,
        int $circuitBreakerThreshold = 5,
        float $circuitBreakerCooldown = 30.0,
        int $maxRetries = 3,
    ) {
        parent::__construct(
            clientId: $clientId,
            clientSecret: $clientSecret,
            prod: $prod,
            baseUrl: $baseUrl,
            signatureKey: $signatureKey,
            rateLimit: $rateLimit,
            circuitBreakerThreshold: $circuitBreakerThreshold,
            circuitBreakerCooldown: $circuitBreakerCooldown,
            maxRetries: $maxRetries,
        );

        if ($token !== null) {
            $this->authTokenObject = $token;
        }

        if ($autoAuth && $token === null) {
            $this->authenticate();
        }
    }

    public function refreshToken(): void
    {
        if (!$this->isAuthenticated()) {
            throw new AuthenticationException('No valid refresh token available.');
        }

        if (!isset($this->authTokenObject['refresh_token'])) {
            throw new AuthenticationException('No valid refresh token available.');
        }

        $response = $this->post(self::ENDPOINTS['REFRESH'], [
            'refresh' => $this->authTokenObject['refresh_token'],
        ]);

        $this->authTokenObject['access_token'] = $response['access'] ?? null;
    }

    public function requestToken(string $username, string $password): array
    {
        $tokenData = $this->post(self::ENDPOINTS['AUTH_TOKEN'], [
            'username' => $username,
            'password' => $password,
        ]);

        $this->authTokenObject = $tokenData;

        return $tokenData;
    }

    public function ping(): array
    {
        return $this->get(self::ENDPOINTS['PING']);
    }

    public function accountLookup(string $accountNumber): array
    {
        return $this->post(self::ENDPOINTS['LOOKUP'], [
            'account_number' => $accountNumber,
        ]);
    }

    public function accountBalance(string $accountNumber): array
    {
        return $this->post(self::ENDPOINTS['BALANCE'], [
            'account_number' => $accountNumber,
        ]);
    }

    public function deposit(
        string $creditAccount,
        string $creditAccountHolder,
        float $amount,
        string $description,
        string $externalReference = '',
        string $pinCode = '',
        string $externalCode = '',
    ): array {
        $payload = new DepositPayload(
            creditAccount: $creditAccount,
            creditAccountHolder: $creditAccountHolder,
            amount: $amount,
            description: $description,
            externalReference: $externalReference ?: $this->generateReference(),
            pinCode: $pinCode,
            externalCode: $externalCode,
        );

        return $this->post(self::ENDPOINTS['DEPOSIT'], $payload->toArray());
    }

    public function operationLookup(string $operationCode, string $amount): array
    {
        return $this->post(self::ENDPOINTS['OPERATION_LOOKUP'], [
            'operation_code' => $operationCode,
            'amount' => $amount,
        ]);
    }

    public function validateWithdrawal(
        string $externalReference,
        string $pinCode,
        string $agentCode,
        string $amount,
        string $externalCode = '',
        string $validationOperationCode = '',
    ): array {
        $payload = new ValidateWithdrawalPayload(
            externalReference: $externalReference,
            pinCode: $pinCode,
            agentCode: $agentCode,
            amount: $amount,
            externalCode: $externalCode,
            validationOperationCode: $validationOperationCode,
        );

        return $this->post(self::ENDPOINTS['VALIDATE_WITHDRAWAL'], $payload->toArray());
    }

    public function transactionStatus(string $externalReference, string $reference): array
    {
        return $this->post(self::ENDPOINTS['STATUS'], [
            'external_reference' => $externalReference,
            'reference' => $reference,
        ]);
    }
}
