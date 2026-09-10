<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Nexora\Sdk\Http\HttpClient;

/**
 * Hotel sector resource for the Nexora Developer API.
 */
final class Hotel
{
    public function __construct(
        private readonly HttpClient $client
    ) {
    }

    public function listRooms(array $query = []): array
    {
        return $this->client->get('/hotel/rooms', $query);
    }

    public function listReservations(array $query = []): array
    {
        return $this->client->get('/hotel/reservations', $query);
    }

    public function createReservation(array $data): array
    {
        return $this->client->post('/hotel/reservations', $data);
    }

    public function listGuests(array $query = []): array
    {
        return $this->client->get('/hotel/guests', $query);
    }
}
