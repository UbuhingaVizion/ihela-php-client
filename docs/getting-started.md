# Getting Started

## Requirements

- PHP 8.1 or later
- Composer 2
- iHela merchant account with client ID, client secret, and PIN code

## Installation

```bash
composer require ihela/api-client
```

## Configuration

Set your credentials via environment variables:

```bash
export IHELA_CLIENT_ID="your-client-id"
export IHELA_CLIENT_SECRET="your-client-secret"
export IHELA_PIN_CODE="your-pin-code"
```

## Merchant Client

The `MerchantClient` handles bill payments, cash-in, bank lookups, and
customer verification.

```php
<?php

use Ihela\Merchant\MerchantClient;

$client = new MerchantClient(
    getenv('IHELA_CLIENT_ID'),
    getenv('IHELA_CLIENT_SECRET'),
    getenv('IHELA_PIN_CODE'),
);

// Production mode
$client = new MerchantClient(
    getenv('IHELA_CLIENT_ID'),
    getenv('IHELA_CLIENT_SECRET'),
    getenv('IHELA_PIN_CODE'),
    prod: true,
);
```

## Environment Modes

| Mode | Base URL | Use |
|------|----------|-----|
| Test (default) | `https://testgate.ihela.online/` | Sandbox, no real money |
| Production | `https://gate.ihela.online/` | Live, requires VPN |

## Next Steps

- [Authentication Guide](authentication.md) — OAuth2 flows
- [API Reference](api.md) — All available methods
- [Security Best Practices](security.md)
