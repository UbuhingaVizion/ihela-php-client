<?php

declare(strict_types=1);

/**
 * iHela PHP Client — Examples
 *
 * Before running:
 *   cp .env.example .env
 *   Fill in your credentials in .env
 */

require __DIR__ . '/../vendor/autoload.php';

use Ihela\Agent\AgentClient;
use Ihela\Auth\MerchantAuthorizationClient;
use Ihela\Banking\BankingClient;
use Ihela\Merchant\MerchantClient;

// Load environment variables (use vlucas/phpdotenv in production)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        putenv(trim($line));
    }
}

$clientId = getenv('IHELA_CLIENT_ID');
$clientSecret = getenv('IHELA_CLIENT_SECRET');
$pinCode = getenv('IHELA_PIN_CODE');

if (!$clientId || !$clientSecret) {
    echo "Error: Set IHELA_CLIENT_ID and IHELA_CLIENT_SECRET in .env\n";
    exit(1);
}

echo "=== Merchant Client ===\n\n";

$merchant = new MerchantClient($clientId, $clientSecret, $pinCode);

$banks = $merchant->getBankList();
echo "Banks: " . json_encode($banks, JSON_PRETTY_PRINT) . "\n\n";

if (!empty($banks['objects'])) {
    $bankSlug = $banks['objects'][0]['slug'];

    $lookup = $merchant->customerLookup($bankSlug, accountNumber: '30001-01-00-16-01-00');
    echo "Customer Lookup: " . json_encode($lookup, JSON_PRETTY_PRINT) . "\n\n";

    $cashin = $merchant->cashinClient($bankSlug, '76077736', 1000, 'EXAMPLE-' . rand(), 'Example cashin');
    echo "Cashin: " . json_encode($cashin, JSON_PRETTY_PRINT) . "\n\n";
}

echo "=== Banking Client ===\n\n";

$banking = new BankingClient($clientId, $clientSecret);

$ping = $banking->ping();
echo "Ping: " . json_encode($ping, JSON_PRETTY_PRINT) . "\n\n";

$fee = $banking->transactionFee('BIF', 'withdrawal', '5000');
echo "Transaction Fee: " . json_encode($fee, JSON_PRETTY_PRINT) . "\n\n";

echo "=== Agent Client ===\n\n";

$agent = new AgentClient($clientId, $clientSecret);
echo "Agent authenticated: " . ($agent->isAuthenticated() ? 'yes' : 'no') . "\n\n";

echo "=== OAuth2 SSO ===\n\n";

$auth = new MerchantAuthorizationClient($clientId, $clientSecret);
$loginUrl = $auth->getAuthorizationUrl('https://example.com/oauth/callback/');
echo "Login URL: $loginUrl\n";
