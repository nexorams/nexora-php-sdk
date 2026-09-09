<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Nexora\Sdk\Http\HttpClient;

final class Domains
{
    public function __construct(
        private readonly HttpClient $client
    ) {
    }

    /**
     * List custom domains configured for an organization.
     *
     * @param string $organizationId Target organization ID
     * @return array<int, array<string, mixed>>
     */
    public function list(string $organizationId): array
    {
        /** @var array<int, array<string, mixed>> */
        return $this->client->get('/organizations/' . rawurlencode($organizationId) . '/domains');
    }

    /**
     * Connect a custom domain to an organization.
     * Returns required DNS verification records (TXT and CNAME).
     *
     * @param string $organizationId Target organization ID
     * @param string $hostname Fully-qualified domain name (e.g. portal.example.com)
     * @param bool $isPrimary Whether this hostname should be primary
     * @param array<string, mixed> $extra Optional additional parameters
     * @return array<string, mixed>
     */
    public function create(
        string $organizationId,
        string $hostname,
        bool $isPrimary = false,
        array $extra = []
    ): array {
        $payload = array_merge([
            'hostname' => $hostname,
            'isPrimary' => $isPrimary,
        ], $extra);

        /** @var array<string, mixed> */
        return $this->client->post('/organizations/' . rawurlencode($organizationId) . '/domains', $payload);
    }

    /**
     * Trigger authoritative DNS verification for an organization's custom domain.
     * Note: Domains cannot be manually marked verified from the client.
     *
     * @param string $organizationId Target organization ID
     * @param string $domainId Domain record ID to verify
     * @return array<string, mixed>
     */
    public function verify(string $organizationId, string $domainId): array
    {
        /** @var array<string, mixed> */
        return $this->client->post(
            '/organizations/' . rawurlencode($organizationId) . '/domains/' . rawurlencode($domainId) . '/verify',
            []
        );
    }
}
