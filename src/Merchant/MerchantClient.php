<?php

declare(strict_types=1);

namespace Ihela\Merchant;

use Ihela\Core\OAuth2Client;

use function array_filter;
use function sprintf;

class MerchantClient extends OAuth2Client
{
    private const ENDPOINTS = [
        'USER_INFO' => 'api/v1/connected-user/',
        'BILL_INIT' => 'api/v1/payments/bill-init/',
        'BILL_VERIFY' => 'api/v1/payments/bill-check/',
        'CASHIN' => 'api/v1/payments/cash-in/',
        'BANKS_ALL' => 'api/v1/payments/bank/',
        'BANKS_CASHIN' => 'api/v1/payments/bank/cashin/',
        'BANKS_CASHOUT' => 'api/v1/payments/bank/cashout/',
        'LOOKUP' => 'api/v1/bank/%s/account/lookup/',
    ];

    private ?string $pinCode;

    public function __construct(
        string $clientId,
        string $clientSecret,
        ?string $pinCode = null,
        bool $prod = false,
        ?string $baseUrl = null,
        ?string $signatureKey = null,
        int $rateLimit = 0,
        int $circuitBreakerThreshold = 5,
        float $circuitBreakerCooldown = 30.0,
        int $maxRetries = 3,
        bool $autoAuth = true,
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

        $this->pinCode = $pinCode;

        if ($autoAuth) {
            $this->authenticate();
        }
    }

    public function setPinCode(string $pinCode): void
    {
        $this->pinCode = $pinCode;
    }

    public function initBill(
        int $amount,
        string $user,
        string $description,
        string $reference,
        ?string $bank = null,
        ?string $bankClientId = null,
        ?string $redirectUri = null,
        ?string $pinCode = null,
        ?string $merchantDescription = null,
        ?string $paymentProductId = null,
    ): array {
        $billData = array_filter([
            'debit_bank' => $bank,
            'debit_account' => $user,
            'amount' => $amount,
            'description' => $description,
            'merchant_description' => $merchantDescription ?? $description,
            'merchant_reference' => $reference,
            'redirect_uri' => $redirectUri,
            'payment_product_id' => $paymentProductId,
            'pin_code' => $pinCode ?? $this->pinCode,
        ], fn ($v) => $v !== null);

        return $this->post(self::ENDPOINTS['BILL_INIT'], $billData);
    }

    public function verifyBill(
        string $billCode,
        string $merchantReference,
        ?string $pinCode = null,
    ): array {
        return $this->post(self::ENDPOINTS['BILL_VERIFY'], [
            'bill_code' => $billCode,
            'merchant_reference' => $merchantReference,
            'pin_code' => $pinCode ?? $this->pinCode,
        ]);
    }

    public function cashinClient(
        string $bankSlug,
        string $account,
        int $amount,
        string $merchantReference,
        string $description,
        ?string $pinCode = null,
        ?string $creditAccountHolder = null,
        string $currency = 'BIF',
    ): array {
        $cashinData = array_filter([
            'credit_bank' => $bankSlug,
            'credit_account' => $account,
            'credit_account_holder' => $creditAccountHolder ?? $account,
            'amount' => $amount,
            'merchant_reference' => $merchantReference,
            'description' => $description,
            'pin_code' => $pinCode ?? $this->pinCode,
            'currency' => $currency,
        ], fn ($v) => $v !== null);

        return $this->post(self::ENDPOINTS['CASHIN'], $cashinData);
    }

    public function getBankList(): array
    {
        return $this->get(self::ENDPOINTS['BANKS_ALL']);
    }

    public function getCashinBankList(): array
    {
        return $this->get(self::ENDPOINTS['BANKS_CASHIN']);
    }

    public function getCashoutBankList(): array
    {
        return $this->get(self::ENDPOINTS['BANKS_CASHOUT']);
    }

    public function customerLookup(
        string $bankSlug,
        ?string $customerId = null,
        ?string $accountNumber = null,
    ): array {
        $path = sprintf(self::ENDPOINTS['LOOKUP'], $bankSlug);
        $queryParam = $accountNumber ?? $customerId;

        return $this->get($path, ['account_number' => $queryParam]);
    }

    public function getUserInfo(): array
    {
        return $this->get(self::ENDPOINTS['USER_INFO']);
    }
}
