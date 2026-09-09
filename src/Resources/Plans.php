<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Nexora\Sdk\Http\HttpClient;

final class Plans
{
    public function __construct(
        private readonly HttpClient $client
    ) {
    }

    /**
     * List available subscription plans, tiers, and quotas.
     *
     * @param array<string, mixed> $query Filters: organizationType, sector, productContext (ORGANIZATION|DEVELOPER)
     * @return array<int, array<string, mixed>>
     */
    public function list(array $query = []): array
    {
        /** @var array<int, array<string, mixed>> */
        return $this->client->get('/plans', $query);
    }
}
