# Authentication

iHela uses OAuth2 for authentication. The SDK supports two grant types.

## Client Credentials (Server-to-Server)

Used by `MerchantClient`, `BankingClient`, and `AgentClient`.
Authentication happens automatically on construction.

```php
use Ihela\Merchant\MerchantClient;

$client = new MerchantClient(
    getenv('IHELA_CLIENT_ID'),
    getenv('IHELA_CLIENT_SECRET'),
);

// Token is automatically fetched and stored
$token = $client->getAccessToken();

// Force re-authentication
$client->authenticate();

// Clear cached token
$client->clearToken();
```

## Authorization Code (User-Facing SSO)

Used by `MerchantAuthorizationClient` for login-with-iHela flows.

### Step 1: Generate Login URL

```php
use Ihela\Auth\MerchantAuthorizationClient;

$auth = new MerchantAuthorizationClient(
    getenv('IHELA_CLIENT_ID'),
    getenv('IHELA_CLIENT_SECRET'),
);

$loginUrl = $auth->getAuthorizationUrl('https://your-app.com/oauth/callback/');

// Redirect the user to $loginUrl
header('Location: ' . $loginUrl);
```

### Step 2: Handle Callback

iHela redirects the user to your callback URL with a `code` parameter.

```php
$auth = new MerchantAuthorizationClient(
    getenv('IHELA_CLIENT_ID'),
    getenv('IHELA_CLIENT_SECRET'),
);

try {
    $auth->authenticate($_GET['code'], 'https://your-app.com/oauth/callback/');

    // Access token
    $token = $auth->getAccessToken();

    // User information
    $user = $auth->getUserInfo();
    // $user['id'], $user['email'], $user['name']

} catch (ApiException $e) {
    // Handle authentication failure
}
```

## Laravel Example

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
            config('services.ihela.client_id'),
            config('services.ihela.client_secret'),
        );

        return redirect($auth->getAuthorizationUrl(
            route('ihela.callback')
        ));
    }

    public function callback(Request $request)
    {
        $auth = new MerchantAuthorizationClient(
            config('services.ihela.client_id'),
            config('services.ihela.client_secret'),
        );

        $auth->authenticate($request->get('code'), route('ihela.callback'));

        $user = $auth->getUserInfo();

        // Find or create user, log them in
    }
}
```

## Symfony Example

```php
<?php

namespace App\Controller;

use Ihela\Auth\MerchantAuthorizationClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IhelaAuthController extends AbstractController
{
    public function redirect(): Response
    {
        $auth = new MerchantAuthorizationClient(
            $this->getParameter('ihela.client_id'),
            $this->getParameter('ihela.client_secret'),
        );

        return $this->redirect($auth->getAuthorizationUrl(
            $this->generateUrl('ihela_callback', [], 0)
        ));
    }

    public function callback(Request $request): Response
    {
        $auth = new MerchantAuthorizationClient(
            $this->getParameter('ihela.client_id'),
            $this->getParameter('ihela.client_secret'),
        );

        $auth->authenticate(
            $request->query->get('code'),
            $this->generateUrl('ihela_callback', [], 0)
        );

        $user = $auth->getUserInfo();

        // Handle user session
    }
}
```
