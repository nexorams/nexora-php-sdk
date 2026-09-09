<?php

declare(strict_types=1);

namespace Nexora\Sdk\Exception;

use Throwable;

/**
 * Thrown when the server rejects malformed or invalid request parameters (HTTP 400 or 422).
 *
 * Typical error codes:
 * - VALIDATION_ERROR
 * - INVALID_REQUEST
 * - MISSING_API_KEY
 * - INVALID_API_KEY_FORMAT
 */
class ValidationException extends NexoraException
{
    public function __construct(
        string $message = 'Invalid request parameters.',
        string $errorCode = 'VALIDATION_ERROR',
        int $statusCode = 400,
        ?string $requestId = null,
        mixed $details = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode, $statusCode, $requestId, $details, $previous);
    }
}
