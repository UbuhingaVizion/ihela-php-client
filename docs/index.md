# iHela PHP Client

PHP SDK for the [iHela Credit Union API](https://bankingdocs.ihela.bi/) for financial services in Burundi.

## Installation

```bash
composer require ihela/api-client
```

## Quick Start

```php
<?php

use Ihela\Merchant\MerchantClient;

$client = new MerchantClient(
    getenv('IHELA_CLIENT_ID'),
    getenv('IHELA_CLIENT_SECRET'),
    getenv('IHELA_PIN_CODE'),
);

$banks = $client->getBankList();
$bill = $client->initBill(2000, 'user@example.com', 'Payment', 'ref-001');
```

## Framework Integration

Works with any PHP application. Install with Composer and start using it
immediately. Compatible with Laravel service providers, Symfony bundles,
or plain PHP scripts.
