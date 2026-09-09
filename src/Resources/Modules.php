<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Nexora\Sdk\Http\HttpClient;

final class Modules
{
    public function __construct(
        private readonly HttpClient $client
    ) {
    }

    /**
     * List the full system catalog of Nexora extensible modules, optionally filtered by sector.
     *
     * @param array<string, mixed> $query Query parameters: organizationType or sector
     * @return array<int, array<string, mixed>>
     */
    public function list(array $query = []): array
    {
        /** @var array<int, array<string, mixed>> */
        return $this->client->get('/modules', $query);
    }

    /**
     * Update active feature modules for a specific organization.
     *
     * @param string $organizationId Target organization ID
     * @param array<string> $modules List of module keys to enable
     * @return array<string, mixed>
     */
    public function update(string $organizationId, array $modules): array
    {
        /** @var array<string, mixed> */
        return $this->client->patch('/organizations/' . rawurlencode($organizationId) . '/modules', [
            'modules' => $modules,
        ]);
    }

    /**
     * List core modules and active project module entitlements for the authenticated workspace.
     *
     * @return array<string, mixed>|array<int, array<string, mixed>>
     */
    public function projectModules(): array
    {
        /** @var array<string, mixed>|array<int, array<string, mixed>> */
        return $this->client->get('/project/modules');
    }

    /**
     * Retrieve developer account module credit summary.
     * Unlimited entitlements are represented semantically with 'unlimited' => true and 'limit' => null.
     *
     * @return array<string, mixed>
     */
    public function credits(): array
    {
        /** @var array<string, mixed> */
        return $this->client->get('/project/module-credits');
    }
}
