<?php

declare(strict_types=1);

namespace Nexora\Sdk\Exception;

use Throwable;

/**
 * Thrown on network connection failures, DNS resolution issues, or HTTP request timeouts.
 * Preserves the underlying exception without leaking sensitive request authentication headers.
 */
class NetworkException extends NexoraException
{
    public function __construct(
        string $message = 'Network connection failed.',
        string $errorCode = 'NETWORK_ERROR',
        int $statusCode = 0,
        ?string $requestId = null,
        mixed $details = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode, $statusCode, $requestId, $details, $previous);
    }
}
