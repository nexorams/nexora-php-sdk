# Nexora PHP SDK

Official PHP client for the [Nexora Developer Platform](https://nexoragms.com/developers).

Programmatically provision, automate, and orchestrate multi-tenant sector management systems (Schools, Hospitals, Hotels, Pharmacies, Enterprises) with the Nexora Developer API (`/developer/v1`).

---

## Requirements

* **PHP:** `>= 8.1` (tested on PHP 8.1, 8.2, 8.3, and 8.4)
* **Extensions:** `ext-json`, `ext-curl`, `ext-mbstring`, `ext-openssl`
* **Composer:** `>= 2.0`

---

## Installation

Install the official package via Composer:

```bash
composer require nexorams/sdk
```

---

## Authentication

All requests to the Nexora Developer API are authenticated using your Developer API Key transmitted via HTTP Bearer token:

```http
Authorization: Bearer nx_test_...
```

### TEST vs LIVE Environments

Nexora uses distinct key prefixes to guarantee environment isolation:

* **Sandbox / Test:** `nx_test_...` — Provisions isolated sandbox organizations with automatic 30-day trials.
* **Production / Live:** `nx_live_...` — Provisions real-world tenant organizations. Webhook endpoints require valid HTTPS destinations passing SSRF security validation.

Environment authority is determined server-side from your API key.

> **CRITICAL SECURITY NOTICE:**  
> Nexora Developer API keys are server-side credentials. **NEVER** expose your API key in Blade templates, frontend JavaScript, mobile app bundles, public HTML, or client-accessible environment files.

---

## Quick Start

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Nexora\Sdk\Nexora;

// Initialize the Nexora client
$nexora = new Nexora(
    apiKey: $_ENV['NEXORA_API_KEY']
);

// List available modular features
$modules = $nexora->modules()->list([
    'organizationType' => 'hospital',
]);

// Provision a new tenant hospital
$organization = $nexora->organizations()->create([
    'name' => 'Apex Specialist Hospital',
    'type' => 'HOSPITAL',
    'country' => 'NG',
    'currency' => 'NGN',
    'timezone' => 'Africa/Lagos',
    'owner' => [
        'firstName' => 'Clara',
        'lastName' => 'Oswald',
        'email' => 'clara.oswald@example.com',
        'phone' => '+2348012345678',
    ],
    'modules' => ['telemedicine', 'pharmacy'],
], idempotencyKey: 'hospital-provision-001');

echo "Provisioned: " . $organization['name'] . PHP_EOL;
echo "Portal URL: " . $organization['portalUrl'] . PHP_EOL;
```

---

## Resource Usage Guide

### Organizations

Programmatic provisioning, lifecycle management, and metadata updates for tenant management systems.

```php
// List organizations authorized under your developer workspace
$organizations = $nexora->organizations()->list([
    'page' => 1,
    'limit' => 20,
    'type' => 'hospital',
    'status' => 'ACTIVE',
]);

// Retrieve organization details by ID
$org = $nexora->organizations()->get('org_12345');

// Update branding colors, address, or contact phone
$updated = $nexora->organizations()->update('org_12345', [
    'name' => 'Apex Medical Centre',
    'primaryColor' => '#0052cc',
    'theme' => 'dark',
]);

// Update active feature modules
$modules = $nexora->organizations()->updateModules('org_12345', [
    'telemedicine',
    'pharmacy',
    'laboratory',
]);

// Inspect subscription & 30-day trial status
$subscription = $nexora->organizations()->getSubscription('org_12345');

// Manage custom white-label hostnames
$domains = $nexora->organizations()->getDomains('org_12345');
$newDomain = $nexora->organizations()->addDomain('org_12345', 'portal.apexmedical.org', isPrimary: true);
$verification = $nexora->organizations()->verifyDomain('org_12345', $newDomain['id']);
```

### Users

Provision practitioners, teachers, staff members, and client users into tenant organizations.

```php
// Provision a staff doctor with an invitation token
$user = $nexora->users()->create('org_12345', [
    'firstName' => 'John',
    'lastName' => 'Watson',
    'email' => 'dr.watson@example.com',
    'role' => 'doctor',
    'phone' => '+2348098765432',
]);

// List active user memberships in an organization
$doctors = $nexora->users()->list('org_12345', [
    'role' => 'doctor',
]);
```

> **Note:** The Developer API enforces role safety. Attempting to assign platform administration roles (`SUPER_ADMIN`, `PLATFORM_SUPPORT`) will result in a `PermissionException` (`FORBIDDEN_ROLE`).

### Modules

Query available modular capabilities and inspect developer account module credit entitlements.

```php
// List all extensible modules compatible with schools
$modules = $nexora->modules()->list([
    'organizationType' => 'school',
]);

// List core and active module entitlements for your developer workspace
$projectModules = $nexora->modules()->projectModules();

// Retrieve developer account credit summary
$credits = $nexora->modules()->credits();

if ($credits['unlimited'] ?? false) {
    echo "Unlimited module credits available." . PHP_EOL;
} else {
    echo "Credits: {$credits['usedCredits']} used / {$credits['remainingCredits']} remaining." . PHP_EOL;
}
```

### Plans

Inspect public subscription tiers, sector limits, and pricing.

```php
// List active subscription tiers
$plans = $nexora->plans()->list([
    'organizationType' => 'hospital',
    'productContext' => 'ORGANIZATION',
]);
```

### Subscriptions

Inspect authoritative tenant subscription tiers, trial periods, and resource quotas.

```php
$sub = $nexora->subscriptions()->get('org_12345');

echo "Plan: " . $sub['planName'] . PHP_EOL;
echo "Status: " . $sub['status'] . PHP_EOL;
echo "Trial Ends: " . $sub['trialEndsAt'] . PHP_EOL;
```

### Domains

Connect and verify custom white-label hostnames.

```php
// List connected domains
$domains = $nexora->domains()->list('org_12345');

// Connect a custom vanity domain
$domain = $nexora->domains()->create('org_12345', 'portal.apexmedical.org', isPrimary: true);

// Trigger authoritative DNS verification
$result = $nexora->domains()->verify('org_12345', $domain['id']);
```

### Webhooks

Register real-time event listener endpoints and rotate cryptographic signing secrets.

```php
// Register a new webhook listener
$endpoint = $nexora->webhooks()->create([
    'url' => 'https://api.yourdomain.com/webhooks/nexora',
    'events' => ['organization.provisioned', 'user.created'],
    'description' => 'Production billing listener',
]);

// Stash the show-once signing secret safely
$signingSecret = $endpoint['secret']; // whsec_...

// List configured endpoints
$endpoints = $nexora->webhooks()->list();

// Rotate secret
$rotated = $nexora->webhooks()->rotateSecret($endpoint['id']);

// Send a test ping event
$ping = $nexora->webhooks()->test($endpoint['id']);

// Disable endpoint
$nexora->webhooks()->disable($endpoint['id']);

// Delete endpoint
$nexora->webhooks()->delete($endpoint['id']);
```

### Webhook Signature Verification

Incoming webhook POST requests are signed using HMAC-SHA256 (`t=<timestamp>,v1=<signature>`). Verify the signature using constant-time comparison (`hash_equals`) and automatic replay protection:

```php
use Nexora\Sdk\Nexora;

// Retrieve the raw request body and signature header
$rawPayload = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_X_NEXORA_SIGNATURE'] ?? '';
$secret = $_ENV['NEXORA_WEBHOOK_SECRET']; // whsec_...

// Verify the signature (default tolerance is 300 seconds)
$isValid = Nexora::verifyWebhookSignature(
    payload: $rawPayload,
    header: $signatureHeader,
    secret: $secret,
    toleranceSeconds: 300
);

if (!$isValid) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid webhook signature or expired timestamp']);
    exit;
}

// Process the authentic webhook payload
$event = json_decode($rawPayload, true);
```

### Deliveries

Audit real-time delivery logs, HTTP response codes, and trigger redeliveries.

```php
// List failed deliveries
$failed = $nexora->deliveries()->list([
    'status' => 'FAILED',
    'page' => 1,
    'limit' => 50,
]);

// Inspect delivery error trace
$delivery = $nexora->deliveries()->get('del_12345');

// Manually trigger immediate event redelivery
$retry = $nexora->deliveries()->retry('del_12345');
```

### Usage & Observability

Monitor monthly request volume, error distributions, and project quota status.

```php
// Retrieve monthly telemetry
$usage = $nexora->usage()->summary([
    'period' => '2026-09',
]);

// Retrieve authenticated developer project profile
$project = $nexora->usage()->getProject();
```

---

## Error Handling

The SDK provides a clean exception hierarchy mapping directly to server response codes. All exceptions expose correlation `requestId`, `statusCode`, and `errorCode`:

```php
use Nexora\Sdk\Exception\AuthenticationException;
use Nexora\Sdk\Exception\PermissionException;
use Nexora\Sdk\Exception\ValidationException;
use Nexora\Sdk\Exception\RateLimitException;
use Nexora\Sdk\Exception\NetworkException;
use Nexora\Sdk\Exception\NexoraException;

try {
    $nexora->organizations()->create([...]);
} catch (AuthenticationException $e) {
    // 401: Invalid or revoked API key
    error_log("Auth error [{$e->getErrorCode()}]: {$e->getMessage()}");
} catch (PermissionException $e) {
    // 403: Missing API scope or role forbidden
    error_log("Permission denied: {$e->getMessage()} (Request ID: {$e->getRequestId()})");
} catch (ValidationException $e) {
    // 400 or 422: Invalid parameters
    error_log("Validation error: {$e->getMessage()}");
} catch (RateLimitException $e) {
    // 429: Too Many Requests (Rate limit or monthly quota)
    $retryAfter = $e->getRetryAfter(); // in seconds
    error_log("Rate limited! Retry after {$retryAfter}s");
} catch (NetworkException $e) {
    // Transport failure, timeout, or DNS failure
    error_log("Network failure: {$e->getMessage()}");
} catch (NexoraException $e) {
    // General API failure (e.g. 500 server error)
    error_log("Nexora API Error [{$e->getStatusCode()}]: {$e->getMessage()}");
}
```

---

## Idempotency

Mutating write operations like organization provisioning support the `Idempotency-Key` header:

```php
$organization = $nexora->organizations()->create(
    data: [
        'name' => 'Apex Clinic',
        'type' => 'HOSPITAL',
        'country' => 'NG',
        'owner' => [
            'firstName' => 'Amelia',
            'lastName' => 'Pond',
            'email' => 'amelia@example.com',
        ],
    ],
    idempotencyKey: 'order_checkout_ref_98765'
);
```

---

## Framework Integration Examples

### Laravel Integration

Install the package via Composer:

```bash
composer require nexorams/sdk
```

Add your credentials in `.env`:

```dotenv
NEXORA_API_KEY=nx_live_xxxxxxxxxxxxxxxxxxxxxxxx
NEXORA_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxx
```

Register the configuration in `config/services.php`:

```php
'nexora' => [
    'api_key' => env('NEXORA_API_KEY'),
    'webhook_secret' => env('NEXORA_WEBHOOK_SECRET'),
],
```

Bind the client in `app/Providers/AppServiceProvider.php`:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexora\Sdk\Nexora;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Nexora::class, function () {
            return new Nexora(
                apiKey: config('services.nexora.api_key')
            );
        });
    }
}
```

Use in a Controller:

```php
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexora\Sdk\Nexora;

class OrganizationController extends Controller
{
    public function store(Request $request, Nexora $nexora): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'type' => 'required|in:SCHOOL,HOSPITAL,HOTEL,PHARMACY,ENTERPRISE',
            'country' => 'required|string|size:2',
            'owner.first_name' => 'required|string',
            'owner.last_name' => 'required|string',
            'owner.email' => 'required|email',
        ]);

        $org = $nexora->organizations()->create($validated);

        return response()->json($org, 201);
    }
}
```

Webhook Controller with CSRF exemption:

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Nexora\Sdk\Nexora;

class NexoraWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $isValid = Nexora::verifyWebhookSignature(
            payload: $request->getContent(),
            header: $request->header('X-Nexora-Signature', ''),
            secret: config('services.nexora.webhook_secret')
        );

        if (!$isValid) {
            return response('Invalid signature', 400);
        }

        $event = $request->json()->all();
        // Dispatch job or event listener...

        return response('OK', 200);
    }
}
```

---

## Security & Compliance

1. **Secret Masking:** The `Nexora` client implements custom `__debugInfo()` and `__serialize()` methods to ensure API keys are never exposed in logs, crash dumps, or error reports.
2. **Timing Attack Protection:** Webhook verification always uses `hash_equals()`.
3. **Replay Attack Protection:** Webhook signatures include a timestamp (`t=<timestamp>`) validated against a configurable tolerance window.

---

## Support & Resources

* [Nexora Developer Portal](https://nexoragms.com/developers)
* [API Reference & Documentation](https://api.nexoragms.com/developer/v1/docs)
* [Developer Community](https://nexoragms.com/community)

---

## License

The Nexora PHP SDK is open-sourced software licensed under the [MIT license](LICENSE).
# nexora-php-sdk
