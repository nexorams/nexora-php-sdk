<?php

declare(strict_types=1);

namespace Nexora\Sdk\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Nexora\Sdk\Exception\AuthenticationException;
use Nexora\Sdk\Exception\NetworkException;
use Nexora\Sdk\Exception\NexoraException;
use Nexora\Sdk\Exception\PermissionException;
use Nexora\Sdk\Exception\RateLimitException;
use Nexora\Sdk\Exception\ValidationException;
use Nexora\Sdk\Support\Arr;
use Nexora\Sdk\Support\Version;

class HttpClient
{
    public const DEFAULT_BASE_URL = 'https://api.nexoragms.com/developer/v1';
    public const DEFAULT_TIMEOUT = 30.0;

    private string $apiKey;
    private string $baseUrl;
    private float $timeout;
    private ClientInterface $client;

    public function __construct(
        string $apiKey,
        ?string $baseUrl = null,
        float $timeout = self::DEFAULT_TIMEOUT,
        ?ClientInterface $client = null
    ) {
        $this->apiKey = trim($apiKey);
        $rawBaseUrl = $baseUrl ?: self::DEFAULT_BASE_URL;
        $this->baseUrl = rtrim($rawBaseUrl, '/');
        $this->timeout = $timeout;

        $this->client = $client ?? new GuzzleClient([
            'base_uri' => $this->baseUrl . '/',
            'timeout' => $this->timeout,
            'http_errors' => true,
        ]);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }

    /**
     * Dispatch an HTTP GET request.
     *
     * @param array<string, mixed>|RequestOptions $options
     */
    public function get(string $path, array|RequestOptions $options = []): mixed
    {
        $opts = $options instanceof RequestOptions ? $options : new RequestOptions(query: $options);
        return $this->request('GET', $path, null, $opts);
    }

    /**
     * Dispatch an HTTP POST request.
     *
     * @param ?array<string, mixed> $body
     * @param array<string, mixed>|RequestOptions $options
     */
    public function post(string $path, ?array $body = null, array|RequestOptions $options = []): mixed
    {
        $opts = $options instanceof RequestOptions ? $options : new RequestOptions(
            query: $options['query'] ?? [],
            idempotencyKey: $options['idempotencyKey'] ?? ($options['idempotency_key'] ?? null)
        );
        return $this->request('POST', $path, $body, $opts);
    }

    /**
     * Dispatch an HTTP PATCH request.
     *
     * @param ?array<string, mixed> $body
     * @param array<string, mixed>|RequestOptions $options
     */
    public function patch(string $path, ?array $body = null, array|RequestOptions $options = []): mixed
    {
        $opts = $options instanceof RequestOptions ? $options : new RequestOptions(
            query: $options['query'] ?? [],
            idempotencyKey: $options['idempotencyKey'] ?? ($options['idempotency_key'] ?? null)
        );
        return $this->request('PATCH', $path, $body, $opts);
    }

    /**
     * Dispatch an HTTP DELETE request.
     *
     * @param array<string, mixed>|RequestOptions $options
     */
    public function delete(string $path, array|RequestOptions $options = []): mixed
    {
        $opts = $options instanceof RequestOptions ? $options : new RequestOptions(query: $options);
        return $this->request('DELETE', $path, null, $opts);
    }

    /**
     * Core HTTP request handler with authentication, correlation ID, idempotency, and error mapping.
     */
    public function request(string $method, string $path, ?array $body = null, ?RequestOptions $options = null): mixed
    {
        $cleanPath = ltrim($path, '/');
        $options ??= new RequestOptions();

        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
            'User-Agent' => Version::USER_AGENT,
        ];

        if ($body !== null) {
            $headers['Content-Type'] = 'application/json';
        }

        if ($options->idempotencyKey !== null && trim($options->idempotencyKey) !== '') {
            $headers['Idempotency-Key'] = trim($options->idempotencyKey);
        }

        foreach ($options->headers as $k => $v) {
            if (strcasecmp($k, 'Authorization') !== 0) {
                $headers[$k] = $v;
            }
        }

        $guzzleOptions = [
            'headers' => $headers,
        ];

        if ($options->timeout !== null) {
            $guzzleOptions['timeout'] = $options->timeout;
        }

        if (!empty($options->query)) {
            $guzzleOptions['query'] = Arr::filterNulls($options->query);
        }

        if ($body !== null) {
            $guzzleOptions['json'] = $body;
        }

        $url = $this->baseUrl . '/' . ltrim($path, '/');

        try {
            $response = $this->client->request($method, $url, $guzzleOptions);
            $parsed = ResponseParser::parseJson($response);
            return ResponseParser::unwrap($parsed);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : 500;
            $requestId = $response ? ($response->getHeaderLine('X-Request-Id') ?: null) : null;
            $retryAfterHeader = $response ? ($response->getHeaderLine('Retry-After') ?: null) : null;
            $retryAfter = $retryAfterHeader !== null && is_numeric($retryAfterHeader) ? (int) $retryAfterHeader : null;

            $errorPayload = $response ? ResponseParser::parseJson($response) : [];
            $errData = is_array($errorPayload) && isset($errorPayload['error']) && is_array($errorPayload['error'])
                ? $errorPayload['error']
                : (is_array($errorPayload) ? $errorPayload : []);

            $message = $errData['message'] ?? ($errorPayload['message'] ?? $e->getMessage());
            $errorCode = $errData['code'] ?? ($errorPayload['code'] ?? ('HTTP_' . $statusCode));
            $requestId = $errData['requestId'] ?? ($errorPayload['requestId'] ?? $requestId);
            $details = $errData['details'] ?? $errorPayload;

            $this->throwStructuredException(
                statusCode: $statusCode,
                message: (string) $message,
                errorCode: (string) $errorCode,
                requestId: $requestId ? (string) $requestId : null,
                details: $details,
                retryAfter: $retryAfter,
                previous: $e
            );
        } catch (ConnectException $e) {
            throw new NetworkException(
                message: 'Network connection failed: ' . $e->getMessage(),
                errorCode: 'NETWORK_ERROR',
                statusCode: 0,
                previous: $e
            );
        } catch (GuzzleException $e) {
            throw new NetworkException(
                message: 'HTTP transport error: ' . $e->getMessage(),
                errorCode: 'TRANSPORT_ERROR',
                statusCode: 0,
                previous: $e
            );
        }
    }

    /**
     * Maps HTTP status codes to specific NexoraException subclasses.
     *
     * @never-returns
     */
    private function throwStructuredException(
        int $statusCode,
        string $message,
        string $errorCode,
        ?string $requestId,
        mixed $details,
        ?int $retryAfter,
        \Throwable $previous
    ): never {
        throw match ($statusCode) {
            401 => new AuthenticationException($message, $errorCode, $statusCode, $requestId, $details, $previous),
            403 => new PermissionException($message, $errorCode, $statusCode, $requestId, $details, $previous),
            400, 422 => new ValidationException($message, $errorCode, $statusCode, $requestId, $details, $previous),
            429 => new RateLimitException($message, $errorCode, $statusCode, $requestId, $details, $retryAfter, $previous),
            default => new NexoraException($message, $errorCode, $statusCode, $requestId, $details, $previous),
        };
    }

    /**
     * Prevents accidental credential leakage during var_dump / print_r.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $masked = strlen($this->apiKey) > 12
            ? substr($this->apiKey, 0, 8) . '••••••••' . substr($this->apiKey, -4)
            : '••••••••';

        return [
            'baseUrl' => $this->baseUrl,
            'timeout' => $this->timeout,
            'apiKey' => $masked,
        ];
    }
}
