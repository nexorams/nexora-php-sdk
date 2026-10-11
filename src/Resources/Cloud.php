<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Nexora\Sdk\Exception\ValidationException;
use Nexora\Sdk\Http\HttpClient;

/**
 * Nexora Cloud generic, schema-driven resources.
 *
 * Nothing is generated or hard-coded per application: application and entity keys are data,
 * resolved by the server against the application's schema, the organization's grants and your
 * API key's scopes (cloud:read / cloud:write / cloud:delete).
 */
final class Cloud
{
    public function __construct(
        private readonly HttpClient $client,
        private readonly ?string $defaultApplicationId = null
    ) {
    }

    /** Organizations that granted this project access to an installed Cloud application. */
    public function installations(): array
    {
        return $this->client->get('/cloud/installations');
    }

    public function app(string $applicationId): CloudApplication
    {
        return new CloudApplication($this->client, $applicationId);
    }

    public function entity(string $entityKey, ?string $applicationId = null): CloudEntity
    {
        return new CloudEntity($this->client, $applicationId ?? $this->requireDefault(), $entityKey);
    }

    public function schema(): array
    {
        return $this->app($this->requireDefault())->schema();
    }

    private function requireDefault(): string
    {
        if ($this->defaultApplicationId === null || $this->defaultApplicationId === '') {
            throw new ValidationException(
                message: 'Pass applicationId to the Nexora client or use $nexora->cloud()->app($applicationId).',
                errorCode: 'APPLICATION_ID_REQUIRED',
                statusCode: 400
            );
        }
        return $this->defaultApplicationId;
    }
}
