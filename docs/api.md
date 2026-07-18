# API Reference

## Merchant Client

Namespace: `Ihela\Merchant\MerchantClient`

### `initBill()`

Initialize a payment bill.

```php
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
): array
```

### `verifyBill()`

Check the status of a bill.

```php
public function verifyBill(
    string $billCode,
    string $merchantReference,
    ?string $pinCode = null,
): array
```

### `cashinClient()`

Send money to a customer's account.

```php
public function cashinClient(
    string $bankSlug,
    string $account,
    int $amount,
    string $merchantReference,
    string $description,
    ?string $pinCode = null,
    ?string $creditAccountHolder = null,
    string $currency = 'BIF',
): array
```

### `getBankList()`

Get all banks.

### `getCashinBankList()`

Get cash-in enabled banks.

### `getCashoutBankList()`

Get cash-out enabled banks.

### `customerLookup()`

Look up a customer by bank and account/customer ID.

```php
public function customerLookup(
    string $bankSlug,
    ?string $customerId = null,
    ?string $accountNumber = null,
): array
```

### `getUserInfo()`

Get the authenticated merchant's user information.

---

## Banking Client

Namespace: `Ihela\Banking\BankingClient`

### `ping()`

Test connectivity.

### `transactionFee()`

Get the fee for a transaction.

```php
public function transactionFee(
    string $currency,
    string $operationType,
    string $amount,
): array
```

### `accountLookup()`

Look up an account.

```php
public function accountLookup(string $accountNumber): array
```

### `accountBalance()`

Check an account balance.

```php
public function accountBalance(string $accountNumber): array
```

### `deposit()`

Make a deposit.

```php
public function deposit(
    string $creditAccount,
    string $creditAccountHolder,
    float $amount,
    string $description,
    string $externalReference = '',
    string $pinCode = '',
    string $externalCode = '',
): array
```

### `withdrawal()`

Make a withdrawal.

```php
public function withdrawal(
    string $debitAccount,
    string $debitAccountHolder,
    float $amount,
    string $description,
    string $externalReference = '',
    string $pinCode = '',
): array
```

### `statement()`

Get a mini-statement.

```php
public function statement(string $accountNumber): array
```

### `transactionStatus()`

Check transaction status.

```php
public function transactionStatus(
    string $externalReference,
    string $reference,
): array
```

---

## Agent Client

Namespace: `Ihela\Agent\AgentClient`

### `accountLookup()`

Look up an account.

### `accountBalance()`

Check an account balance.

### `deposit()`

Make an agent deposit.

### `operationLookup()`

Look up an operation by code.

```php
public function operationLookup(string $operationCode, string $amount): array
```

### `validateWithdrawal()`

Validate a withdrawal request.

```php
public function validateWithdrawal(
    string $externalReference,
    string $pinCode,
    string $agentCode,
    string $amount,
    string $externalCode = '',
    string $validationOperationCode = '',
): array
```

### `transactionStatus()`

Check transaction status.

---

## Authorization Client

Namespace: `Ihela\Auth\MerchantAuthorizationClient`

### `getAuthorizationUrl()`

Generate an OAuth2 authorization URL for the login-with-iHela flow.

```php
public function getAuthorizationUrl(string $redirectUri, ?string $state = null): string
```

### `authenticate()`

Exchange an authorization code for an access token.

```php
public function authenticate(string $authorizationCode, string $redirectUri): void
```

### `getUserInfo()`

Get the authenticated user's profile.

### `billInit()`

Initialize a bill on behalf of the authenticated user.

### `billVerify()`

Verify a bill on behalf of the authenticated user.

---

## Exceptions

| Class | Description |
|-------|-------------|
| `Ihela\Core\Exception\IhelaException` | Base exception |
| `Ihela\Core\Exception\AuthenticationException` | Auth failure |
| `Ihela\Core\Exception\ApiException` | API error, with `isRetryable()` |
| `Ihela\Core\Exception\RateLimitException` | Rate limit exceeded |
| `Ihela\Core\Exception\CircuitOpenException` | Circuit breaker open |
