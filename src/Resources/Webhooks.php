<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Nexora\Sdk\Http\HttpClient;
use Nexora\Sdk\Webhooks\SignatureVerifier;

final class Webhooks
{
    public function __construct(
        private readonly HttpClient $client
    ) {
    }

    /**
     * Register a new webhook endpoint.
     * The plain signing secret (whsec_...) is returned only once upon creation.
     *
     * @param array<string, mixed> $data Webhook parameters: url, events, name, description
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        /** @var array<string, mixed> */
        return $this->client->post('/webhook-endpoints', $data);
    }

    /**
     * List registered webhook endpoints for the authenticated project.
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        /** @var array<int, array<string, mixed>> */
        return $this->client->get('/webhook-endpoints');
    }

    /**
     * Retrieve webhook endpoint configuration and delivery stats by ID.
     *
     * @param string $id Webhook endpoint ID
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        /** @var array<string, mixed> */
        return $this->client->get('/webhook-endpoints/' . rawurlencode($id));
    }

    /**
     * Alias for get(id).
     *
     * @param string $id Webhook endpoint ID
     * @return array<string, mixed>
     */
    public function retrieve(string $id): array
    {
        return $this->get($id);
    }

    /**
     * Update a registered webhook endpoint (URL, subscribed events, description, status).
     *
     * @param string $id Webhook endpoint ID
     * @param array<string, mixed> $data Updatable fields: url, events, description, status
     * @return array<string, mixed>
     */
    public function update(string $id, array $data): array
    {
        /** @var array<string, mixed> */
        return $this->client->patch('/webhook-endpoints/' . rawurlencode($id), $data);
    }

    /**
     * Rotate signing secret for a registered webhook endpoint.
     * Immediately invalidates previous secret and returns the new plain secret once.
     *
     * @param string $id Webhook endpoint ID
     * @return array<string, mixed>
     */
    public function rotateSecret(string $id): array
    {
        /** @var array<string, mixed> */
        return $this->client->post('/webhook-endpoints/' . rawurlencode($id) . '/rotate-secret', []);
    }

    /**
     * Disable a webhook endpoint to pause event dispatches.
     *
     * @param string $id Webhook endpoint ID
     * @return array<string, mixed>
     */
    public function disable(string $id): array
    {
        /** @var array<string, mixed> */
        return $this->client->post('/webhook-endpoints/' . rawurlencode($id) . '/disable', []);
    }

    /**
     * Send a synthetic test ping event (webhook.test) to verify endpoint connectivity.
     *
     * @param string $id Webhook endpoint ID
     * @param array<string, mixed> $params Optional eventType override
     * @return array<string, mixed>
     */
    public function test(string $id, array $params = []): array
    {
        /** @var array<string, mixed> */
        return $this->client->post('/webhook-endpoints/' . rawurlencode($id) . '/test', $params);
    }

    /**
     * Delete a registered webhook endpoint.
     *
     * @param string $id Webhook endpoint ID
     * @return array<string, mixed>
     */
    public function delete(string $id): array
    {
        /** @var array<string, mixed> */
        return $this->client->delete('/webhook-endpoints/' . rawurlencode($id));
    }

    /**
     * Cryptographically verify an incoming webhook signature.
     *
     * @param string $payload Raw webhook request body
     * @param string $header The 'X-Nexora-Signature' or 'Nexora-Signature' header
     * @param string $secret The endpoint's HMAC signing secret (whsec_...)
     * @param int $toleranceSeconds Maximum allowed clock skew (default 300 seconds)
     * @return bool True if valid, false otherwise
     */
    public function verifySignature(
        string $payload,
        string $header,
        string $secret,
        int $toleranceSeconds = SignatureVerifier::DEFAULT_TOLERANCE_SECONDS
    ): bool {
        return SignatureVerifier::verify($payload, $header, $secret, $toleranceSeconds);
    }
}
