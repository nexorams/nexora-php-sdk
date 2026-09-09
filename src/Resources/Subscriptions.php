<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Nexora\Sdk\Http\HttpClient;

final class Subscriptions
{
    public function __construct(
        private readonly HttpClient $client
    ) {
    }

    /**
     * Retrieve active subscription, tier, limits, and 30-day trial status for an organization.
     *
     * @param string $organizationId Target organization ID
     * @return array<string, mixed>
     */
    public function get(string $organizationId): array
    {
        /** @var array<string, mixed> */
        return $this->client->get('/organizations/' . rawurlencode($organizationId) . '/subscription');
    }

    /**
     * Alias for get(organizationId).
     *
     * @param string $organizationId Target organization ID
     * @return array<string, mixed>
     */
    public function retrieve(string $organizationId): array
    {
        return $this->get($organizationId);
    }
}
