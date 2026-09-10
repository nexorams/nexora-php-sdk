<?php

declare(strict_types=1);

namespace Nexora\Sdk;

use GuzzleHttp\ClientInterface;
use Nexora\Sdk\Exception\ValidationException;
use Nexora\Sdk\Http\HttpClient;
use Nexora\Sdk\Resources\Deliveries;
use Nexora\Sdk\Resources\Domains;
use Nexora\Sdk\Resources\Modules;
use Nexora\Sdk\Resources\Organizations;
use Nexora\Sdk\Resources\Plans;
use Nexora\Sdk\Resources\Subscriptions;
use Nexora\Sdk\Resources\Usage;
use Nexora\Sdk\Resources\Users;
use Nexora\Sdk\Resources\Webhooks;
use Nexora\Sdk\Resources\School;
use Nexora\Sdk\Resources\Hospital;
use Nexora\Sdk\Resources\Hotel;
use Nexora\Sdk\Resources\Pharmacy;
use Nexora\Sdk\Resources\Company;
use Nexora\Sdk\Webhooks\SignatureVerifier;

/**
 * Official PHP Client for the Nexora Developer Platform.
 *
 * Programmatically provision and orchestrate multi-tenant sector management
 * systems (Schools, Hospitals, Hotels, Pharmacies, Enterprises) with the Nexora Developer API.
 */
class Nexora
{
    private string $apiKey;
    private string $environment;
    private string $baseUrl;
    private HttpClient $http;

    private Organizations $organizations;
    private Users $users;
    private Modules $modules;
    private Plans $plans;
    private Subscriptions $subscriptions;
    private Domains $domains;
    private Webhooks $webhooks;
    private Deliveries $deliveries;
    private Usage $usage;
    private School $school;
    private Hospital $hospital;
    private Hotel $hotel;
    private Pharmacy $pharmacy;
    private Company $company;

    /**
     * Initialize the Nexora client.
     *
     * @param string $apiKey Nexora Developer API Key ('nx_test_...' for Sandbox or 'nx_live_...' for Live).
     * @param ?string $baseUrl Custom API base URL (defaults to 'https://api.nexoragms.com/developer/v1').
     * @param float $timeout Request timeout in seconds (default 30.0).
     * @param ?ClientInterface $httpClient Optional custom Guzzle client (useful for mock testing).
     * @throws ValidationException
     */
    public function __construct(
        string $apiKey,
        ?string $baseUrl = null,
        float $timeout = HttpClient::DEFAULT_TIMEOUT,
        ?ClientInterface $httpClient = null
    ) {
        $trimmedKey = trim($apiKey);
        if ($trimmedKey === '') {
            throw new ValidationException(
                message: 'API key is required to initialize the Nexora SDK.',
                errorCode: 'MISSING_API_KEY',
                statusCode: 400
            );
        }

        if (!str_starts_with($trimmedKey, 'nx_test_') && !str_starts_with($trimmedKey, 'nx_live_')) {
            throw new ValidationException(
                message: "Invalid API key prefix. Expected 'nx_test_' for sandbox or 'nx_live_' for live.",
                errorCode: 'INVALID_API_KEY_FORMAT',
                statusCode: 400
            );
        }

        $this->apiKey = $trimmedKey;
        $this->environment = str_starts_with($trimmedKey, 'nx_live_') ? 'live' : 'sandbox';
        $this->baseUrl = rtrim($baseUrl ?: HttpClient::DEFAULT_BASE_URL, '/');

        $this->http = new HttpClient(
            apiKey: $this->apiKey,
            baseUrl: $this->baseUrl,
            timeout: $timeout,
            client: $httpClient
        );

        $this->organizations = new Organizations($this->http);
        $this->users = new Users($this->http);
        $this->modules = new Modules($this->http);
        $this->plans = new Plans($this->http);
        $this->subscriptions = new Subscriptions($this->http);
        $this->domains = new Domains($this->http);
        $this->webhooks = new Webhooks($this->http);
        $this->deliveries = new Deliveries($this->http);
        $this->usage = new Usage($this->http);

        // Sector-specific resources
        $this->school = new School($this->http);
        $this->hospital = new Hospital($this->http);
        $this->hotel = new Hotel($this->http);
        $this->pharmacy = new Pharmacy($this->http);
        $this->company = new Company($this->http);
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getHttpClient(): HttpClient
    {
        return $this->http;
    }

    public function organizations(): Organizations
    {
        return $this->organizations;
    }

    public function users(): Users
    {
        return $this->users;
    }

    public function modules(): Modules
    {
        return $this->modules;
    }

    public function plans(): Plans
    {
        return $this->plans;
    }

    public function subscriptions(): Subscriptions
    {
        return $this->subscriptions;
    }

    public function domains(): Domains
    {
        return $this->domains;
    }

    public function webhooks(): Webhooks
    {
        return $this->webhooks;
    }

    public function deliveries(): Deliveries
    {
        return $this->deliveries;
    }

    public function usage(): Usage
    {
        return $this->usage;
    }

    public function school(): School
    {
        return $this->school;
    }

    public function hospital(): Hospital
    {
        return $this->hospital;
    }

    public function hotel(): Hotel
    {
        return $this->hotel;
    }

    public function pharmacy(): Pharmacy
    {
        return $this->pharmacy;
    }

    public function company(): Company
    {
        return $this->company;
    }

    /**
     * Magic property accessor to support both `$nexora->organizations` and `$nexora->organizations()`.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'organizations' => $this->organizations,
            'users' => $this->users,
            'modules' => $this->modules,
            'plans' => $this->plans,
            'subscriptions' => $this->subscriptions,
            'domains' => $this->domains,
            'webhooks' => $this->webhooks,
            'deliveries' => $this->deliveries,
            'usage' => $this->usage,
            'school' => $this->school,
            'hospital' => $this->hospital,
            'hotel' => $this->hotel,
            'pharmacy' => $this->pharmacy,
            'company' => $this->company,
            'environment' => $this->environment,
            'baseUrl' => $this->baseUrl,
            default => null,
        };
    }

    /**
     * Static helper to cryptographically verify incoming webhook signatures.
     *
     * @param string $payload Raw webhook request body
     * @param string $header The 'X-Nexora-Signature' or 'Nexora-Signature' header
     * @param string $secret The endpoint's HMAC signing secret (whsec_...)
     * @param int $toleranceSeconds Maximum allowed clock skew (default 300 seconds)
     * @return bool True if valid, false otherwise
     */
    public static function verifyWebhookSignature(
        string $payload,
        string $header,
        string $secret,
        int $toleranceSeconds = SignatureVerifier::DEFAULT_TOLERANCE_SECONDS
    ): bool {
        return SignatureVerifier::verify($payload, $header, $secret, $toleranceSeconds);
    }

    /**
     * Prevents accidental secret exposure in var_dump, print_r, or IDE inspector.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $maskedKey = strlen($this->apiKey) > 12
            ? substr($this->apiKey, 0, 8) . '••••••••' . substr($this->apiKey, -4)
            : '••••••••';

        return [
            'environment' => $this->environment,
            'baseUrl' => $this->baseUrl,
            'apiKey' => $maskedKey,
        ];
    }

    /**
     * Prevent serialization of sensitive client state and raw API keys.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'environment' => $this->environment,
            'baseUrl' => $this->baseUrl,
        ];
    }

    /**
     * Unserialize handler.
     *
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->environment = $data['environment'] ?? 'sandbox';
        $this->baseUrl = $data['baseUrl'] ?? HttpClient::DEFAULT_BASE_URL;
        $this->apiKey = '';
        $this->http = new HttpClient('nx_test_unserialized', $this->baseUrl);
        $this->organizations = new Organizations($this->http);
        $this->users = new Users($this->http);
        $this->modules = new Modules($this->http);
        $this->plans = new Plans($this->http);
        $this->subscriptions = new Subscriptions($this->http);
        $this->domains = new Domains($this->http);
        $this->webhooks = new Webhooks($this->http);
        $this->deliveries = new Deliveries($this->http);
        $this->usage = new Usage($this->http);
    }
}
