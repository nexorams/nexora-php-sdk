<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Nexora\Sdk\Http\HttpClient;

final class CloudApplication
{
    public function __construct(
        private readonly HttpClient $client,
        public readonly string $applicationId
    ) {
    }

    /** Entities and fields the organization exposed to this project. */
    public function schema(): array
    {
        return $this->client->get('/cloud/apps/' . $this->applicationId . '/schema');
    }

    public function entity(string $entityKey): CloudEntity
    {
        return new CloudEntity($this->client, $this->applicationId, $entityKey);
    }
}
