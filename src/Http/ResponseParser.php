<?php

declare(strict_types=1);

namespace Nexora\Sdk\Http;

use JsonException;
use Psr\Http\Message\ResponseInterface;

final class ResponseParser
{
    /**
     * Decodes the response body into an associative array or scalar.
     *
     * @return mixed
     */
    public static function parseJson(ResponseInterface $response): mixed
    {
        $body = (string) $response->getBody();
        if ($body === '') {
            return null;
        }

        try {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ['message' => $body];
        }
    }

    /**
     * Unwraps canonical Nexora Developer API response envelopes:
     * - If paginated ({ data: [...], pagination: {...} }), returns full envelope array.
     * - If single resource ({ data: {...} }), unwraps and returns data.
     * - Otherwise returns decoded response payload.
     *
     * @param mixed $data
     * @return mixed
     */
    public static function unwrap(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        // Paginated list response: preserve full envelope
        if (array_key_exists('data', $data) && array_key_exists('pagination', $data)) {
            return $data;
        }

        // Single resource or unpaginated list wrapped in data: unwrap data
        if (array_key_exists('data', $data) && !array_key_exists('pagination', $data)) {
            return $data['data'];
        }

        return $data;
    }
}
