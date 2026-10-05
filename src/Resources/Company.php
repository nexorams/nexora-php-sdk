<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Nexora\Sdk\Http\HttpClient;
use Nexora\Sdk\Http\RequestOptions;

/**
 * Company/Enterprise sector resource for the Nexora Developer API.
 */
final class Company
{
    public function __construct(
        private readonly HttpClient $client
    ) {
    }

    public function listEmployees(array $query = []): array
    {
        return $this->client->get('/company/employees', $query);
    }

    public function createEmployee(array $data, ?string $idempotencyKey = null): array
    {
        return $this->client->post('/company/employees', $data, new RequestOptions(idempotencyKey: $idempotencyKey));
    }

    public function listAttendance(array $query = []): array
    {
        return $this->client->get('/company/attendance', $query);
    }

    public function recordAttendance(array $data, ?string $idempotencyKey = null): array
    {
        return $this->client->post('/company/attendance', $data, new RequestOptions(idempotencyKey: $idempotencyKey));
    }

    public function listPayroll(array $query = []): array
    {
        return $this->client->get('/company/payroll', $query);
    }
}
