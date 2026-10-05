<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Nexora\Sdk\Http\HttpClient;
use Nexora\Sdk\Http\RequestOptions;

final class Users
{
    public function __construct(
        private readonly HttpClient $client
    ) {
    }

    /**
     * Provision a staff member, practitioner, or client user inside a tenant organization.
     * Generates an invitation token for password setup. Note: Platform admin roles are forbidden.
     *
     * @param string $organizationId Target organization ID
     * @param array<string, mixed> $data User attributes: firstName, lastName, email, role, phone
     * @return array<string, mixed>
     */
    public function create(string $organizationId, array $data, ?string $idempotencyKey = null): array
    {
        /** @var array<string, mixed> */
        return $this->client->post(
            '/organizations/' . rawurlencode($organizationId) . '/users',
            $data,
            new RequestOptions(idempotencyKey: $idempotencyKey)
        );
    }

    /**
     * List safe user memberships in a tenant organization.
     *
     * @param string $organizationId Target organization ID
     * @param array<string, mixed> $query Filter parameters (role, page, limit)
     * @return array<string, mixed> Paginated result with data and pagination keys
     */
    public function list(string $organizationId, array $query = []): array
    {
        /** @var array<string, mixed> */
        return $this->client->get('/organizations/' . rawurlencode($organizationId) . '/users', $query);
    }
}
