<?php

declare(strict_types=1);

namespace Nexora\Sdk\Exception;

use Throwable;

/**
 * Thrown when API key authentication fails (HTTP 401).
 *
 * Typical error codes:
 * - API_KEY_MISSING
 * - API_KEY_INVALID
 * - API_KEY_REVOKED
 * - API_KEY_EXPIRED
 */
class AuthenticationException extends NexoraException
{
    public function __construct(
        string $message = 'Invalid or missing API key credentials.',
        string $errorCode = 'API_KEY_INVALID',
        int $statusCode = 401,
        ?string $requestId = null,
        mixed $details = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode, $statusCode, $requestId, $details, $previous);
    }
}
