<?php

declare(strict_types=1);

namespace Nexora\Sdk\Exception;

use Throwable;

/**
 * Thrown when the client exceeds read/write rate limits or monthly request quotas (HTTP 429).
 *
 * Typical error codes:
 * - RATE_LIMIT_EXCEEDED
 * - API_QUOTA_EXCEEDED
 */
class RateLimitException extends NexoraException
{
    protected ?int $retryAfter;

    public function __construct(
        string $message = 'Rate limit or monthly quota exceeded.',
        string $errorCode = 'RATE_LIMIT_EXCEEDED',
        int $statusCode = 429,
        ?string $requestId = null,
        mixed $details = null,
        ?int $retryAfter = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode, $statusCode, $requestId, $details, $previous);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $arr = parent::toArray();
        $arr['retryAfter'] = $this->retryAfter;
        return $arr;
    }
}
