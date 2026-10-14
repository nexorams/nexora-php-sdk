<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Generator;
use Nexora\Sdk\Exception\ValidationException;
use Nexora\Sdk\Http\HttpClient;
use Nexora\Sdk\Http\RequestOptions;

/**
 * Client for ONE entity of ONE Nexora Cloud application.
 */
final class CloudEntity
{
    public function __construct(
        private readonly HttpClient $client,
        public readonly string $applicationId,
        public readonly string $entityKey
    ) {
        if (preg_match('/^[a-fA-F0-9]{24}$/', $applicationId) !== 1) {
            throw new ValidationException(message: 'applicationId must be a Nexora Cloud application id.', errorCode: 'INVALID_APPLICATION_ID', statusCode: 400);
        }
        if (preg_match('/^[a-z][a-z0-9_]{0,47}$/', $entityKey) !== 1) {
            throw new ValidationException(
                message: "Invalid entity key '{$entityKey}'. Entity keys are lowercase letters, digits and underscores.",
                errorCode: 'INVALID_ENTITY_KEY',
                statusCode: 400
            );
        }
    }

    private function base(): string
    {
        return '/cloud/apps/' . $this->applicationId . '/entities/' . $this->entityKey . '/records';
    }

    /**
     * @param array{page?:int,limit?:int,status?:string} $query
     * @param array<string,string> $headers e.g. ['X-Organization-Id' => 'org_2'] to override the default tenant
     * @return array{data: array<int, array<string,mixed>>, pagination: array<string,mixed>}
     */
    public function list(array $query = [], array $headers = []): array
    {
        return $this->client->get($this->base(), new RequestOptions(query: $query, headers: $headers));
    }

    /** Iterates every record, fetching pages lazily. */
    public function listAll(int $limit = 100, ?string $status = null): Generator
    {
        $page = 1;
        while (true) {
            $result = $this->list(['page' => $page, 'limit' => $limit, 'status' => $status]);
            foreach ($result['data'] as $record) {
                yield $record;
            }
            $totalPages = (int) ($result['pagination']['totalPages'] ?? 1);
            if ($page >= $totalPages || $result['data'] === []) {
                return;
            }
            $page++;
        }
    }

    public function get(string $recordId): array
    {
        return $this->client->get($this->base() . '/' . rawurlencode($recordId));
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $relations relation field key => record id (to-one) or list of ids (to-many)
     */
    public function create(array $data = [], array $relations = [], ?string $idempotencyKey = null): array
    {
        return $this->client->post($this->base(), self::body($data, $relations), new RequestOptions(idempotencyKey: $idempotencyKey));
    }

    public function update(string $recordId, array $data = [], array $relations = []): array
    {
        return $this->client->patch($this->base() . '/' . rawurlencode($recordId), self::body($data, $relations));
    }

    /** Archives (soft-deletes) a record; data is never hard-deleted through the API. */
    public function archive(string $recordId): array
    {
        return $this->client->delete($this->base() . '/' . rawurlencode($recordId));
    }

    /** Webhook event names for this entity, e.g. cloud.members.created. */
    public function events(): array
    {
        return [
            'created' => 'cloud.' . $this->entityKey . '.created',
            'updated' => 'cloud.' . $this->entityKey . '.updated',
            'archived' => 'cloud.' . $this->entityKey . '.archived',
        ];
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $relations
     * @return array<string,mixed>
     */
    private static function body(array $data, array $relations): array
    {
        $body = [];
        if ($data !== []) {
            $body['data'] = $data;
        }
        if ($relations !== []) {
            $body['relations'] = $relations;
        }
        return $body;
    }
}
