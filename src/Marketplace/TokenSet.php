<?php

declare(strict_types=1);

namespace Nexora\Sdk\Marketplace;

/**
 * Tokens from the Marketplace token endpoint. Keep these on your server.
 * `claims` carries the verified claims (`userId` is present when a user opened the app from Nexora).
 */
final class TokenSet
{
    /**
     * @param string[]                  $scope
     * @param array<string, mixed>|null $claims
     */
    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly int $expiresIn,
        public readonly int $expiresAt,
        public readonly ?int $refreshTokenExpiresIn = null,
        public readonly array $scope = [],
        public readonly ?string $installationId = null,
        public readonly ?array $claims = null,
    ) {
    }

    /** True when the access token expires within `$withinSeconds` (default 60): time to refresh. */
    public function isExpiring(int $withinSeconds = 60): bool
    {
        return time() >= $this->expiresAt - $withinSeconds;
    }

    public function __debugInfo(): array
    {
        return ['accessToken' => '••••', 'refreshToken' => '••••', 'expiresAt' => $this->expiresAt, 'scope' => $this->scope, 'installationId' => $this->installationId];
    }
}
