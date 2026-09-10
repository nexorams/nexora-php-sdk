<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Nexora\Sdk\Http\HttpClient;

/**
 * Hospital sector resource for the Nexora Developer API.
 */
final class Hospital
{
    public function __construct(
        private readonly HttpClient $client
    ) {
    }

    public function listPatients(array $query = []): array
    {
        return $this->client->get('/hospital/patients', $query);
    }

    public function createPatient(array $data): array
    {
        return $this->client->post('/hospital/patients', $data);
    }

    public function listAppointments(array $query = []): array
    {
        return $this->client->get('/hospital/appointments', $query);
    }

    public function createAppointment(array $data): array
    {
        return $this->client->post('/hospital/appointments', $data);
    }

    public function listVitals(array $query = []): array
    {
        return $this->client->get('/hospital/vitals', $query);
    }

    public function recordVitals(array $data): array
    {
        return $this->client->post('/hospital/vitals', $data);
    }
}
