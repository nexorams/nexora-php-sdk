<?php

declare(strict_types=1);

namespace Nexora\Sdk\Marketplace;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Nexora\Sdk\Exception\AuthenticationException;
use Nexora\Sdk\Exception\NetworkException;
use Nexora\Sdk\Exception\NexoraException;
use Nexora\Sdk\Exception\PermissionException;
use Nexora\Sdk\Exception\RateLimitException;
use Nexora\Sdk\Exception\ValidationException;

/**
 * Marketplace app authorization for apps hosted outside Nexora (server side only: the client secret must never reach a browser).
 *
 *   $auth = new MarketplaceAuth(getenv('NEXORA_CLIENT_ID'), getenv('NEXORA_CLIENT_SECRET'));
 *   $tokens = $auth->exchangeCode($_GET['code'], 'https://app.example.com/nexora/launch');
 *   $nexora = new Nexora(apiKey: $tokens->accessToken);   // the SDK accepts an installation access token (nxi_…) like an API key
 */
final class MarketplaceAuth
{
    public const DEFAULT_BASE_URL = 'https://api.nexoragms.com/api/v1';

    private readonly string $clientId;
    private readonly string $clientSecret;
    private readonly string $baseUrl;
    private readonly ClientInterface $http;

    public function __construct(string $clientId, string $clientSecret, string $baseUrl = self::DEFAULT_BASE_URL, ?ClientInterface $httpClient = null, float $timeout = 15.0)
    {
        if (trim($clientId) === '' || trim($clientSecret) === '') {
            throw new ValidationException('clientId and clientSecret are required.', 'MISSING_CLIENT_CREDENTIALS', 400);
        }
        $this->clientId = trim($clientId);
        $this->clientSecret = trim($clientSecret);
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->http = $httpClient ?? new GuzzleClient(['timeout' => $timeout, 'http_errors' => true]);
    }

    /** Trade the one-time `$code` (valid 60 seconds, single use) for tokens. `$redirectUri` is the address the code was sent to. */
    public function exchangeCode(string $code, string $redirectUri, ?string $codeVerifier = null): TokenSet
    {
        if ($code === '' || $redirectUri === '') {
            throw new ValidationException('code and redirectUri are required.', 'INVALID_REQUEST', 400);
        }
        $params = ['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => $redirectUri];
        if ($codeVerifier !== null && $codeVerifier !== '') {
            $params['code_verifier'] = $codeVerifier;
        }
        return $this->tokenSet($this->post('/marketplace/oauth/token', $params));
    }

    /** Refresh tokens work ONCE: the response holds the next one, which you must store before using the new access token. */
    public function refresh(string $refreshToken): TokenSet
    {
        if ($refreshToken === '') {
            throw new ValidationException('refreshToken is required.', 'INVALID_REQUEST', 400);
        }
        return $this->tokenSet($this->post('/marketplace/oauth/token', ['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken]));
    }

    /**
     * Is this access token still good, and what does it say? Always returns; `['active' => false]` for anything not live and issued to your client.
     *
     * @return array<string, mixed>
     */
    public function introspect(string $token): array
    {
        if ($token === '') {
            throw new ValidationException('token is required.', 'INVALID_REQUEST', 400);
        }
        $out = $this->post('/marketplace/oauth/introspect', ['token' => $token]);
        return ($out['active'] ?? null) === true ? $out : ['active' => false];
    }

    /**
     * The signing secret for the installation behind an access token from the connection the ORGANIZATION ADMINISTRATOR made (not one issued for a person who
     * launched the app). Call it on your server and keep the secret out of logs and the browser. During a rotation grace window `previousSecret` is also returned;
     * the signature verifier accepts a delivery signed with either (pass the one you hold).
     *
     * @return array<string, mixed>
     */
    public function webhookSecret(string $token): array
    {
        if ($token === '') {
            throw new ValidationException('token is required.', 'INVALID_REQUEST', 400);
        }
        return $this->post('/marketplace/oauth/webhook-secret', ['token' => $token]);
    }

    /** End a token: a refresh token ends the whole connection, an access token only itself. Always succeeds for an authenticated client. */
    public function revoke(string $token): void
    {
        if ($token === '') {
            throw new ValidationException('token is required.', 'INVALID_REQUEST', 400);
        }
        $this->post('/marketplace/oauth/revoke', ['token' => $token]);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function post(string $path, array $params): array
    {
        try {
            $response = $this->http->request('POST', $this->baseUrl . $path, [
                'headers' => ['Accept' => 'application/json', 'User-Agent' => 'Nexora-PHP-SDK-Marketplace'],
                'json' => $params + ['client_id' => $this->clientId, 'client_secret' => $this->clientSecret],
            ]);
            $data = json_decode((string) $response->getBody(), true);
            return is_array($data) ? $data : [];
        } catch (ConnectException $e) {
            throw new NetworkException('Network connection failed.', 'NETWORK_ERROR', 0, null, null, $e);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response === null) {
                throw new NetworkException('Network connection failed.', 'NETWORK_ERROR', 0, null, null, $e);
            }
            $payload = json_decode((string) $response->getBody(), true);
            $err = is_array($payload) && isset($payload['error']) && is_array($payload['error']) ? $payload['error'] : [];
            $status = $response->getStatusCode();
            $message = (string) ($err['message'] ?? ('Token request failed with status ' . $status));
            $code = (string) ($err['code'] ?? ('HTTP_' . $status));
            $requestId = isset($err['requestId']) ? (string) $err['requestId'] : ($response->getHeaderLine('X-Request-Id') ?: null);
            $alias = isset($err['alias']) && is_string($err['alias']) ? $err['alias'] : null;
            $exception = match (true) {
                $status === 401 => new AuthenticationException($message, $code, $status, $requestId, null, $e),
                $status === 403 => new PermissionException($message, $code, $status, $requestId, null, $e),
                $status === 429 => new RateLimitException($message, $code, $status, $requestId, null, ctype_digit($response->getHeaderLine('Retry-After')) ? (int) $response->getHeaderLine('Retry-After') : null, $e),
                $status === 400 || $status === 422 => new ValidationException($message, $code, $status, $requestId, null, $e),
                default => new NexoraException($message, $code, $status, $requestId, null, $e),
            };
            throw $exception->withAlias($alias);
        } catch (GuzzleException $e) {
            throw new NetworkException('The token request failed.', 'NETWORK_ERROR', 0, null, null, $e);
        }
    }

    /** @param array<string, mixed> $data */
    private function tokenSet(array $data): TokenSet
    {
        $expiresIn = (int) ($data['expires_in'] ?? 0);
        $scope = isset($data['scope']) && is_string($data['scope']) && $data['scope'] !== '' ? explode(' ', $data['scope']) : [];
        return new TokenSet(
            accessToken: (string) $data['access_token'],
            refreshToken: (string) $data['refresh_token'],
            expiresIn: $expiresIn,
            expiresAt: time() + $expiresIn,
            refreshTokenExpiresIn: isset($data['refresh_token_expires_in']) ? (int) $data['refresh_token_expires_in'] : null,
            scope: $scope,
            installationId: isset($data['installation_id']) ? (string) $data['installation_id'] : null,
            claims: isset($data['claims']) && is_array($data['claims']) ? $data['claims'] : null,
        );
    }

    public function __debugInfo(): array
    {
        return ['clientId' => $this->clientId, 'baseUrl' => $this->baseUrl];
    }
}
