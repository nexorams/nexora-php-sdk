<?php

declare(strict_types=1);

namespace Nexora\Sdk\Exception;

use Throwable;

/**
 * Thrown when an API call is rejected due to insufficient scope or forbidden permissions (HTTP 403).
 *
 * Typical error codes:
 * - INSUFFICIENT_SCOPE
 * - FORBIDDEN_ROLE
 * - DEVELOPER_SUSPENDED
 * - PROJECT_INACTIVE
 * - LIMIT_REACHED
 */
class PermissionException extends NexoraException
{
    public function __construct(
        string $message = 'Access denied or insufficient scope.',
        string $errorCode = 'INSUFFICIENT_SCOPE',
        int $statusCode = 403,
        ?string $requestId = null,
        mixed $details = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode, $statusCode, $requestId, $details, $previous);
    }
}
