<?php

declare(strict_types=1);

namespace Nexora\Sdk\Http;

final class RequestOptions
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     * @param ?string $idempotencyKey
     * @param ?float $timeout
     */
    public function __construct(
        public readonly array $query = [],
        public readonly array $headers = [],
        public readonly ?string $idempotencyKey = null,
        public readonly ?float $timeout = null
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @param ?string $idempotencyKey
     */
    public static function create(array $query = [], ?string $idempotencyKey = null, ?float $timeout = null): self
    {
        return new self(
            query: $query,
            idempotencyKey: $idempotencyKey,
            timeout: $timeout
        );
    }
}
