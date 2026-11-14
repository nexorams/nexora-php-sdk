<?php

declare(strict_types=1);

namespace Nexora\Sdk\Exception;

use Exception;
use Throwable;

/**
 * Base exception for all Nexora PHP SDK errors.
 *
 * Provides structured access to the machine-readable error code, HTTP status code,
 * correlation request ID (X-Request-Id), and supplementary error details.
 * Sensitive API keys are strictly sanitized from exception messages and strings.
 */
class NexoraException extends Exception
{
    protected string $errorCode;
    protected int $statusCode;
    protected ?string $requestId;
    protected mixed $details;
    protected ?string $alias = null;

    public function __construct(
        string $message,
        string $errorCode = 'API_ERROR',
        int $statusCode = 500,
        ?string $requestId = null,
        mixed $details = null,
        ?Throwable $previous = null
    ) {
        $sanitizedMessage = self::sanitizeMessage($message);
        parent::__construct($sanitizedMessage, $statusCode, $previous);

        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->requestId = $requestId;
        $this->details = $details;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getDetails(): mixed
    {
        return $this->details;
    }

    /**
     * The specification name for the same condition, when the server sends one (for example SCOPE_NOT_GRANTED for
     * PERMISSION_NOT_GRANTED). The error code itself never changes.
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function withAlias(?string $alias): static
    {
        $this->alias = $alias;
        return $this;
    }

    /** True when the error is `$name` under either its code or its alias. */
    public function is(string $name): bool
    {
        return $this->errorCode === $name || $this->alias === $name;
    }

    /**
     * Sanitize any raw API keys (nx_test_... or nx_live_...) that might appear in error strings.
     */
    protected static function sanitizeMessage(string $message): string
    {
        return preg_replace('/nx_(test|live)_[a-zA-Z0-9_-]+/i', 'nx_$1_••••••••', $message) ?? $message;
    }

    /**
     * Structured array representation safe for logging.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => static::class,
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
            'alias' => $this->alias,
            'status' => $this->statusCode,
            'requestId' => $this->requestId,
            'details' => $this->details,
        ];
    }
}
