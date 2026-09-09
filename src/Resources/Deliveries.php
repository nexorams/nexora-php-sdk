<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Nexora\Sdk\Http\HttpClient;

final class Deliveries
{
    public function __construct(
        private readonly HttpClient $client
    ) {
    }

    /**
     * List recent webhook deliveries and retry attempts across project endpoints.
     *
     * @param array<string, mixed> $query Filter parameters: endpointId, status, page, limit
     * @return array<string, mixed> Paginated result with 'data' and 'pagination' keys
     */
    public function list(array $query = []): array
    {
        /** @var array<string, mixed> */
        return $this->client->get('/webhook-deliveries', $query);
    }

    /**
     * Retrieve delivery attempt details by ID, including payload, headers, duration, and error logs.
     *
     * @param string $id Delivery ID
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        /** @var array<string, mixed> */
        return $this->client->get('/webhook-deliveries/' . rawurlencode($id));
    }

    /**
     * Alias for get(id).
     *
     * @param string $id Delivery ID
     * @return array<string, mixed>
     */
    public function retrieve(string $id): array
    {
        return $this->get($id);
    }

    /**
     * Manually trigger a redelivery of a failed webhook event.
     *
     * @param string $id Delivery ID
     * @return array<string, mixed>
     */
    public function retry(string $id): array
    {
        /** @var array<string, mixed> */
        return $this->client->post('/webhook-deliveries/' . rawurlencode($id) . '/retry', []);
    }
}
