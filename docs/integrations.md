# Framework Integrations

The iHela PHP Client is framework-agnostic. It uses PSR-18 HTTP discovery,
which auto-detects whatever HTTP client your application already has.

## Laravel

### Service Provider

Create a config file `config/ihela.php`:

```php
<?php

return [
    'client_id' => env('IHELA_CLIENT_ID'),
    'client_secret' => env('IHELA_CLIENT_SECRET'),
    'pin_code' => env('IHELA_PIN_CODE'),
    'signature_key' => env('IHELA_SIGNATURE_KEY'),
    'prod' => env('IHELA_PROD', false),
];
```

Add to `.env`:

```ini
IHELA_CLIENT_ID=your-client-id
IHELA_CLIENT_SECRET=your-client-secret
IHELA_PIN_CODE=your-pin-code
IHELA_PROD=false
```

Create a service provider or bind it in `AppServiceProvider`:

```php
<?php

namespace App\Providers;

use Ihela\Merchant\MerchantClient;
use Illuminate\Support\ServiceProvider;

class IhelaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MerchantClient::class, function () {
            return new MerchantClient(
                config('ihela.client_id'),
                config('ihela.client_secret'),
                config('ihela.pin_code'),
                prod: config('ihela.prod'),
                signatureKey: config('ihela.signature_key'),
            );
        });
    }
}
```

### Usage in a Controller

```php
<?php

namespace App\Http\Controllers;

use Ihela\Merchant\MerchantClient;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function initiate(Request $request, MerchantClient $client)
    {
        $amount = $request->integer('amount');
        $user = $request->user()->email;

        $bill = $client->initBill(
            amount: $amount,
            user: $user,
            description: 'Order #' . $request->order_id,
            reference: 'ORDER-' . $request->order_id,
        );

        return redirect($bill['bill']['confirmation_uri']);
    }

    public function verify(Request $request, MerchantClient $client)
    {
        $result = $client->verifyBill(
            billCode: $request->get('code'),
            merchantReference: 'ORDER-' . $request->order_id,
        );

        if ($result['status'] === 'Paid') {
            // Mark order as paid
        }

        return response()->json($result);
    }
}
```

### Queued Bill Verification

```php
<?php

namespace App\Jobs;

use Ihela\Merchant\MerchantClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerifyBillJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $billCode,
        private string $merchantReference,
    ) {}

    public function handle(MerchantClient $client): void
    {
        $result = $client->verifyBill(
            billCode: $this->billCode,
            merchantReference: $this->merchantReference,
        );

        if ($result['status'] === 'Paid') {
            Order::where('reference', $this->merchantReference)->update(['paid' => true]);
        }
    }
}
```

### OAuth2 SSO Controller

```php
<?php

namespace App\Http\Controllers;

use Ihela\Auth\MerchantAuthorizationClient;
use Illuminate\Http\Request;

class IhelaAuthController extends Controller
{
    public function redirect()
    {
        $auth = new MerchantAuthorizationClient(
            config('ihela.client_id'),
            config('ihela.client_secret'),
            prod: config('ihela.prod'),
        );

        return redirect($auth->getAuthorizationUrl(route('ihela.callback')));
    }

    public function callback(Request $request)
    {
        $auth = new MerchantAuthorizationClient(
            config('ihela.client_id'),
            config('ihela.client_secret'),
            prod: config('ihela.prod'),
        );

        $auth->authenticate($request->get('code'), route('ihela.callback'));

        $user = $auth->getUserInfo();

        // Find or create user, log them in
        $localUser = User::firstOrCreate(
            ['ihela_id' => $user['id']],
            ['name' => $user['name'], 'email' => $user['email']],
        );

        auth()->login($localUser);

        return redirect('/dashboard');
    }
}
```

---

## Symfony

### Service Definition

In `config/services.yaml`:

```yaml
parameters:
    ihela.client_id: '%env(IHELA_CLIENT_ID)%'
    ihela.client_secret: '%env(IHELA_CLIENT_SECRET)%'
    ihela.pin_code: '%env(IHELA_PIN_CODE)%'

services:
    Ihela\Merchant\MerchantClient:
        arguments:
            $clientId: '%ihela.client_id%'
            $clientSecret: '%ihela.client_secret%'
            $pinCode: '%ihela.pin_code%'
```

Add to `.env`:

```ini
IHELA_CLIENT_ID=your-client-id
IHELA_CLIENT_SECRET=your-client-secret
IHELA_PIN_CODE=your-pin-code
```

### Usage in a Controller

```php
<?php

namespace App\Controller;

use Ihela\Merchant\MerchantClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends AbstractController
{
    public function initiate(Request $request, MerchantClient $client): Response
    {
        $bill = $client->initBill(
            amount: $request->get('amount'),
            user: $this->getUser()->getEmail(),
            description: 'Order #' . $request->get('order_id'),
            reference: 'ORDER-' . $request->get('order_id'),
        );

        return $this->redirect($bill['bill']['confirmation_uri']);
    }
}
```

### Using Symfony Secrets Vault

For production credentials, use Symfony's built-in secrets management:

```bash
php bin/console secrets:set IHELA_CLIENT_SECRET
```

Then reference in `services.yaml`:

```yaml
parameters:
    ihela.client_secret: '%env(secret:IHELA_CLIENT_SECRET)%'
```

---

## WordPress

### Plugin Structure

Create a plugin at `wp-content/plugins/ihela-payments/ihela-payments.php`:

```php
<?php
/**
 * Plugin Name: iHela Payments
 * Description: Accept iHela payments on your WordPress site.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Ihela\Merchant\MerchantClient;

add_action('admin_menu', function () {
    add_options_page('iHela Settings', 'iHela Payments', 'manage_options', 'ihela-settings', 'ihela_settings_page');
});

function ihela_settings_page() {
    if (isset($_POST['ihela_client_id'])) {
        update_option('ihela_client_id', sanitize_text_field($_POST['ihela_client_id']));
        update_option('ihela_client_secret', sanitize_text_field($_POST['ihela_client_secret']));
        update_option('ihela_pin_code', sanitize_text_field($_POST['ihela_pin_code']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>iHela Payment Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr><th>Client ID</th><td><input type="text" name="ihela_client_id" value="<?php echo esc_attr(get_option('ihela_client_id')); ?>" class="regular-text"></td></tr>
                <tr><th>Client Secret</th><td><input type="password" name="ihela_client_secret" value="<?php echo esc_attr(get_option('ihela_client_secret')); ?>" class="regular-text"></td></tr>
                <tr><th>PIN Code</th><td><input type="password" name="ihela_pin_code" value="<?php echo esc_attr(get_option('ihela_pin_code')); ?>" class="regular-text"></td></tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function ihela_get_client(): MerchantClient {
    return new MerchantClient(
        get_option('ihela_client_id'),
        get_option('ihela_client_secret'),
        get_option('ihela_pin_code'),
    );
}

// Shortcode: [ihela_pay amount="2000" description="Payment"]
add_shortcode('ihela_pay', function ($atts) {
    $atts = shortcode_atts(['amount' => 0, 'description' => 'Payment'], $atts);

    if (isset($_GET['ihela_bill'])) {
        $client = ihela_get_client();
        $result = $client->verifyBill($_GET['ihela_bill'], 'ORDER-' . get_current_user_id());

        if ($result['status'] === 'Paid') {
            return '<p>Payment successful!</p>';
        }
        if ($result['status'] === 'Pending') {
            return '<p>Payment pending. Please check back.</p>';
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = wp_get_current_user();
        $client = ihela_get_client();
        $bill = $client->initBill(
            amount: (int) $atts['amount'],
            user: $user->user_email,
            description: $atts['description'],
            reference: 'ORDER-' . get_current_user_id(),
        );

        return '<p>Redirecting to iHela...</p>'
            . '<meta http-equiv="refresh" content="0;url=' . esc_url($bill['bill']['confirmation_uri'])
            . '?return=' . urlencode(add_query_arg('ihela_bill', $bill['bill']['code'], get_permalink())) . '">';
    }

    return '<form method="post"><button type="submit">Pay ' . esc_html($atts['amount']) . ' BIF</button></form>';
});
```

---

## Joomla

### Component Plugin

In your Joomla component, add Composer autoload to the entry point and
store credentials in the component configuration.

```php
<?php

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/vendor/autoload.php';

use Ihela\Merchant\MerchantClient;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

class PaymentController extends BaseController
{
    private function getClient(): MerchantClient
    {
        $params = Factory::getApplication()->getParams();

        return new MerchantClient(
            $params->get('ihela_client_id'),
            $params->get('ihela_client_secret'),
            $params->get('ihela_pin_code'),
        );
    }

    public function initiate()
    {
        $app = Factory::getApplication();
        $amount = $app->input->getInt('amount');
        $user = Factory::getUser();

        $client = $this->getClient();
        $bill = $client->initBill(
            amount: $amount,
            user: $user->email,
            description: 'Joomla Payment',
            reference: 'JOOMLA-' . $user->id,
        );

        $app->redirect($bill['bill']['confirmation_uri']);
    }

    public function callback()
    {
        $app = Factory::getApplication();
        $code = $app->input->getString('code');

        $client = $this->getClient();
        $result = $client->verifyBill($code, 'JOOMLA-' . Factory::getUser()->id);

        if ($result['status'] === 'Paid') {
            $app->enqueueMessage('Payment successful!');
        }

        $this->setRedirect('index.php');
    }
}
```

Store credentials in your Joomla extension XML manifest as config fields:

```xml
<fieldset name="ihela">
    <field name="ihela_client_id" type="text" label="Client ID" />
    <field name="ihela_client_secret" type="password" label="Client Secret" />
    <field name="ihela_pin_code" type="password" label="PIN Code" />
</fieldset>
```

---

## Drupal

### Custom Module

Create a custom module at `modules/custom/ihela_payments/`.

**`ihela_payments.info.yml`:**

```yaml
name: 'iHela Payments'
type: module
core_version_requirement: ^10 || ^11
```

**`ihela_payments.services.yml`:**

```yaml
services:
  ihela.merchant_client:
    class: Ihela\Merchant\MerchantClient
    arguments:
      - '%ihela.client_id%'
      - '%ihela.client_secret%'
      - '%ihela.pin_code%'
```

In `settings.php`:

```php
$settings['ihela.client_id'] = getenv('IHELA_CLIENT_ID');
$settings['ihela.client_secret'] = getenv('IHELA_CLIENT_SECRET');
$settings['ihela.pin_code'] = getenv('IHELA_PIN_CODE');
```

### Controller Example

```php
<?php

namespace Drupal\ihela_payments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Ihela\Merchant\MerchantClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PaymentController extends ControllerBase
{
    public function __construct(
        private readonly MerchantClient $client,
    ) {}

    public static function create(ContainerInterface $container): static
    {
        return new static($container->get('ihela.merchant_client'));
    }

    public function initiate(): RedirectResponse
    {
        $bill = $this->client->initBill(
            amount: 2000,
            user: \Drupal::currentUser()->getEmail(),
            description: 'Drupal Payment',
            reference: 'DRUPAL-' . \Drupal::currentUser()->id(),
        );

        return new RedirectResponse($bill['bill']['confirmation_uri']);
    }

    public function callback(): array
    {
        $code = \Drupal::request()->query->get('code');
        $result = $this->client->verifyBill($code, 'DRUPAL-' . \Drupal::currentUser()->id());

        return [
            '#markup' => $result['status'] === 'Paid'
                ? $this->t('Payment successful!')
                : $this->t('Payment pending.'),
        ];
    }
}
```

---

## Plain PHP

```php
<?php

require 'vendor/autoload.php';

use Ihela\Merchant\MerchantClient;

$client = new MerchantClient(
    getenv('IHELA_CLIENT_ID'),
    getenv('IHELA_CLIENT_SECRET'),
    getenv('IHELA_PIN_CODE'),
);

$banks = $client->getBankList();

$bill = $client->initBill(
    amount: 2000,
    user: 'client@example.com',
    description: 'Invoice #123',
    reference: 'INV-123',
);

header('Location: ' . $bill['bill']['confirmation_uri']);
```
