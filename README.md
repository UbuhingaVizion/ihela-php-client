# iHela PHP Client

[![Packagist Version](https://img.shields.io/packagist/v/ihela/api-client)](https://packagist.org/packages/ihela/api-client)
[![PHP Versions](https://img.shields.io/packagist/php-v/ihela/api-client)](https://packagist.org/packages/ihela/api-client)
[![License](https://img.shields.io/packagist/l/ihela/api-client)](LICENSE)
[![CI](https://github.com/UbuhingaVizion/ihela-php-client/actions/workflows/ci.yml/badge.svg)](https://github.com/UbuhingaVizion/ihela-php-client/actions/workflows/ci.yml)
[![Docs](https://img.shields.io/badge/docs-mkdocs-blue)](https://UbuhingaVizion.github.io/ihela-php-client/)

PHP SDK for the iHela Credit Union API for financial services in Burundi.
Framework-agnostic — works with **Laravel**, **Symfony**, or any PHP application.

## Installation

```bash
composer require ihela/api-client
```

## Quick Start

```php
<?php

require 'vendor/autoload.php';

use Ihela\Merchant\MerchantClient;

$client = new MerchantClient(
    getenv('IHELA_CLIENT_ID'),
    getenv('IHELA_CLIENT_SECRET'),
    getenv('IHELA_PIN_CODE'),
);
```

## OAuth2 SSO

```php
use Ihela\Auth\MerchantAuthorizationClient;

$auth = new MerchantAuthorizationClient(
    getenv('IHELA_CLIENT_ID'),
    getenv('IHELA_CLIENT_SECRET'),
);

$loginUrl = $auth->getAuthorizationUrl('https://your-app.com/callback/');
// Redirect user to $loginUrl, handle callback with $auth->authenticate($code, $redirectUri)
```

See the **[Authentication](https://UbuhingaVizion.github.io/ihela-php-client/authentication/)** guide for Laravel and Symfony examples.

## Exception Handling

```php
use Ihela\Core\Exception\ApiException;
use Ihela\Core\Exception\AuthenticationException;
use Ihela\Core\Exception\IhelaException;
use Ihela\Core\Exception\RateLimitException;

try {
    $bill = $client->initBill(2000, 'client@example.com', 'Payment', 'ref-001');
} catch (AuthenticationException $e) {
    // Authentication failed. Check credentials.
} catch (ApiException $e) {
    // API error. $e->statusCode, $e->isRetryable()
} catch (RateLimitException $e) {
    // Rate limit exceeded
} catch (IhelaException $e) {
    // Base iHela error
}
```

## Features

- **Merchant Services**: Bill init/verify, cash-in, bank lists, customer lookup
- **Banking Services**: Deposits, withdrawals, account lookup/balance, statements, transaction fees
- **Agent Services**: Operations, withdrawal validation
- **OAuth2**: Client credentials and authorization code flows
- **Framework-Agnostic**: PSR-18 HTTP client — works with Guzzle, Symfony HttpClient, or any PSR-18 implementation
- **Security**: HMAC-SHA256 request signing, input validation, sensitive data masking, HTTPS enforcement

## Security

- Never hardcode credentials; use environment variables or a secrets vault.
- Report vulnerabilities confidentially to **info@ubuviz.com** — see [SECURITY.md](SECURITY.md).
- Rotate exposed credentials immediately.

## Documentation

Full documentation at [UbuhingaVizion.github.io/ihela-php-client](https://UbuhingaVizion.github.io/ihela-php-client/).

## License

MIT — see [LICENSE](LICENSE).
