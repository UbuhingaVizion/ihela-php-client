# Security

## Credential Management

**Never** hardcode credentials. Store them in:

- Environment variables
- A secrets manager (AWS Secrets Manager, HashiCorp Vault)
- Encrypted configuration files

Example `.env` file (never commit):

```ini
IHELA_CLIENT_ID=your-client-id
IHELA_CLIENT_SECRET=your-client-secret
IHELA_PIN_CODE=your-pin-code
```

## Request Signing

Enable HMAC request signing for message integrity verification:

```php
$client = new \Ihela\Merchant\MerchantClient(
    getenv('IHELA_CLIENT_ID'),
    getenv('IHELA_CLIENT_SECRET'),
    getenv('IHELA_PIN_CODE'),
    signatureKey: getenv('IHELA_SIGNATURE_KEY'),
);
```

Each request will include an `X-iHela-Signature` header.

## Input Validation

All financial payloads are validated before transmission:

- Account numbers: pattern `^[A-Z0-9\-]+$`, 3-50 characters
- Amounts: must be greater than 0
- PIN codes: 4-6 digits only
- References: 3-100 characters

Invalid payloads throw `InvalidArgumentException` before any network call.

## Sensitive Data

The SDK automatically masks sensitive fields (`access_token`, `pin_code`,
`client_secret`, etc.) when a PSR-3 logger is configured.

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$client = new MerchantClient(...);
$client->setLogger(new Logger('ihela', [new StreamHandler('php://stdout')]));
```

Log output will show `********` in place of secrets.

## HTTPS Enforcement

- Production and test URLs use HTTPS by default
- Plain HTTP is rejected unless the host is `127.0.0.1` or `localhost`

## Production Notes

- Production gateway access requires the iHela VPN (IP-whitelisted)
- The production base URL is `https://gate.ihela.online/`
- Use client certificate authentication if required:

```php
$client = new \Ihela\Merchant\MerchantClient(
    clientId: getenv('IHELA_CLIENT_ID'),
    clientSecret: getenv('IHELA_CLIENT_SECRET'),
    prod: true,
);
```

## Reporting Vulnerabilities

See [SECURITY.md](https://github.com/UbuhingaVizion/ihela-php-client/blob/develop/SECURITY.md) for our disclosure policy.
