<?php

declare(strict_types=1);

namespace Nexora\Sdk\Webhooks;

class SignatureVerifier
{
    public const DEFAULT_TOLERANCE_SECONDS = 300;

    /**
     * Cryptographically verifies an incoming webhook signature using HMAC-SHA256.
     * Matches the canonical Nexora backend WebhookSigningService algorithm.
     *
     * Supports:
     * 1. Standard Nexora format: "t=1700000000,v1=abcdef..."
     * 2. Direct HMAC hash: "v1=abcdef..." or raw hex
     *
     * @param string $payload Raw request body string (do not decode and re-encode)
     * @param string $header The 'X-Nexora-Signature' or 'Nexora-Signature' header
     * @param string $secret The endpoint's HMAC signing secret (whsec_...)
     * @param int $toleranceSeconds Maximum allowed clock skew in seconds (default 300). Set to 0 to disable.
     * @return bool True if valid, false otherwise
     */
    public static function verify(
        string $payload,
        string $header,
        string $secret,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS
    ): bool {
        if ($payload === '' || $header === '' || $secret === '') {
            return false;
        }

        $timestamp = null;
        $signatureHash = null;

        if (str_contains($header, 't=') && str_contains($header, 'v1=')) {
            $parts = explode(',', $header);
            foreach ($parts as $part) {
                $trimmed = trim($part);
                if (str_contains($trimmed, '=')) {
                    [$k, $v] = explode('=', $trimmed, 2);
                    $k = trim($k);
                    $v = trim($v);
                    if ($k === 't') {
                        if (!is_numeric($v)) {
                            return false;
                        }
                        $timestamp = (int) $v;
                    } elseif ($k === 'v1') {
                        $signatureHash = $v;
                    }
                }
            }
        } elseif (str_starts_with($header, 'v1=')) {
            $signatureHash = trim(substr($header, 3));
        } else {
            $signatureHash = trim($header);
        }

        if ($signatureHash === null || $signatureHash === '') {
            return false;
        }

        // Verify timestamp window to protect against replay attacks
        if ($timestamp !== null && $toleranceSeconds > 0) {
            $now = time();
            if (abs($now - $timestamp) > $toleranceSeconds) {
                return false;
            }
        }

        // 1. Standard timestamped HMAC: hash_hmac('sha256', "{$timestamp}.{$payload}", $secret)
        if ($timestamp !== null) {
            $signedPayload = "{$timestamp}.{$payload}";
            $expectedHash = hash_hmac('sha256', $signedPayload, $secret);
            if (hash_equals(strtolower($signatureHash), strtolower($expectedHash))) {
                return true;
            }
        }

        // 2. Direct fallback HMAC over raw payload without timestamp prefix
        $directExpectedHash = hash_hmac('sha256', $payload, $secret);
        if (hash_equals(strtolower($signatureHash), strtolower($directExpectedHash))) {
            return true;
        }

        return false;
    }
}
