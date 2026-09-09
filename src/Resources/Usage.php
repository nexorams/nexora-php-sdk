<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Nexora\Sdk\Http\HttpClient;

final class Usage
{
    public function __construct(
        private readonly HttpClient $client
    ) {
    }

    /**
     * Retrieve monthly API call volume, successful requests, rate limit hits, and endpoint breakdowns.
     *
     * @param array<string, mixed> $query Query parameters (e.g. 'period' => 'YYYY-MM')
     * @return array<string, mixed>
     */
    public function summary(array $query = []): array
    {
        /** @var array<string, mixed> */
        return $this->client->get('/usage', $query);
    }

    /**
     * Retrieve authenticated developer project profile, environment, and tier quota limits.
     *
     * @return array<string, mixed>
     */
    public function getProject(): array
    {
        /** @var array<string, mixed> */
        return $this->client->get('/project');
    }
}
