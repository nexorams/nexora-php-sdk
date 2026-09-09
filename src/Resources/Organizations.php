<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Nexora\Sdk\Http\HttpClient;
use Nexora\Sdk\Http\RequestOptions;

final class Organizations
{
    public function __construct(
        private readonly HttpClient $client
    ) {
    }

    /**
     * Programmatically provision a new tenant organization.
     * Supported sectors: SCHOOL, HOSPITAL, HOTEL, PHARMACY, ENTERPRISE.
     *
     * @param array<string, mixed> $data Provisioning payload
     * @param ?string $idempotencyKey Optional unique idempotency key to prevent duplicate provisioning
     * @return array<string, mixed>
     */
    public function create(array $data, ?string $idempotencyKey = null): array
    {
        $options = new RequestOptions(idempotencyKey: $idempotencyKey);
        /** @var array<string, mixed> */
        return $this->client->post('/organizations', $data, $options);
    }

    /**
     * List tenant organizations authorized under the authenticated developer project.
     *
     * @param array<string, mixed> $query Query parameters (page, limit, type, status, search, environment)
     * @return array<string, mixed> Paginated result with 'data' and 'pagination' keys
     */
    public function list(array $query = []): array
    {
        /** @var array<string, mixed> */
        return $this->client->get('/organizations', $query);
    }

    /**
     * Retrieve organization details by ID.
     *
     * @param string $id Organization ID
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        /** @var array<string, mixed> */
        return $this->client->get('/organizations/' . rawurlencode($id));
    }

    /**
     * Alias for get(id).
     *
     * @param string $id Organization ID
     * @return array<string, mixed>
     */
    public function retrieve(string $id): array
    {
        return $this->get($id);
    }

    /**
     * Update organization branding, contact info, address, or theme.
     *
     * @param string $id Organization ID
     * @param array<string, mixed> $data Updatable fields
     * @return array<string, mixed>
     */
    public function update(string $id, array $data): array
    {
        /** @var array<string, mixed> */
        return $this->client->patch('/organizations/' . rawurlencode($id), $data);
    }

    /**
     * Update active feature modules for an organization.
     *
     * @param string $id Organization ID
     * @param array<string> $modules List of module keys to enable
     * @return array<string, mixed>
     */
    public function updateModules(string $id, array $modules): array
    {
        /** @var array<string, mixed> */
        return $this->client->patch('/organizations/' . rawurlencode($id) . '/modules', [
            'modules' => $modules,
        ]);
    }

    /**
     * Retrieve active subscription, tier, limits, and 30-day trial status for an organization.
     *
     * @param string $id Organization ID
     * @return array<string, mixed>
     */
    public function getSubscription(string $id): array
    {
        /** @var array<string, mixed> */
        return $this->client->get('/organizations/' . rawurlencode($id) . '/subscription');
    }

    /**
     * List custom domains configured for an organization.
     *
     * @param string $id Organization ID
     * @return array<int, array<string, mixed>>
     */
    public function getDomains(string $id): array
    {
        /** @var array<int, array<string, mixed>> */
        return $this->client->get('/organizations/' . rawurlencode($id) . '/domains');
    }

    /**
     * Connect a custom domain hostname to an organization.
     *
     * @param string $id Organization ID
     * @param string $hostname Fully qualified domain name (e.g. portal.example.com)
     * @param bool $isPrimary Whether this hostname should be primary
     * @return array<string, mixed>
     */
    public function addDomain(string $id, string $hostname, bool $isPrimary = false): array
    {
        /** @var array<string, mixed> */
        return $this->client->post('/organizations/' . rawurlencode($id) . '/domains', [
            'hostname' => $hostname,
            'isPrimary' => $isPrimary,
        ]);
    }

    /**
     * Trigger authoritative DNS verification for an organization's custom domain.
     *
     * @param string $id Organization ID
     * @param string $domainId Domain record ID
     * @return array<string, mixed>
     */
    public function verifyDomain(string $id, string $domainId): array
    {
        /** @var array<string, mixed> */
        return $this->client->post(
            '/organizations/' . rawurlencode($id) . '/domains/' . rawurlencode($domainId) . '/verify',
            []
        );
    }
}
