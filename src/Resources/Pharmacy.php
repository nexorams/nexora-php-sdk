<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Nexora\Sdk\Http\HttpClient;
use Nexora\Sdk\Http\RequestOptions;

/**
 * Pharmacy sector resource for the Nexora Developer API.
 */
final class Pharmacy
{
    public function __construct(
        private readonly HttpClient $client
    ) {
    }

    public function listProducts(array $query = []): array
    {
        return $this->client->get('/pharmacy/products', $query);
    }

    public function createProduct(array $data, ?string $idempotencyKey = null): array
    {
        return $this->client->post('/pharmacy/products', $data, new RequestOptions(idempotencyKey: $idempotencyKey));
    }

    public function listPrescriptions(array $query = []): array
    {
        return $this->client->get('/pharmacy/prescriptions', $query);
    }

    public function listSales(array $query = []): array
    {
        return $this->client->get('/pharmacy/sales', $query);
    }
}
