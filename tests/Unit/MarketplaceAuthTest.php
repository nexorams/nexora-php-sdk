<?php

declare(strict_types=1);

namespace Nexora\Sdk\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Nexora\Sdk\Exception\AuthenticationException;
use Nexora\Sdk\Exception\NetworkException;
use Nexora\Sdk\Exception\NexoraException;
use Nexora\Sdk\Exception\PermissionException;
use Nexora\Sdk\Exception\RateLimitException;
use Nexora\Sdk\Exception\ValidationException;
use Nexora\Sdk\Marketplace\MarketplaceAuth;
use Nexora\Sdk\Marketplace\TokenSet;
use PHPUnit\Framework\TestCase;

final class MarketplaceAuthTest extends TestCase
{
    private const CLIENT_ID = 'nxc_aaaaaaaaaaaaaaaaaaaaaaaa';
    private const CLIENT_SECRET = 'nxs_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';
    private array $history = [];

    private function auth(array $responses): MarketplaceAuth
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));
        return new MarketplaceAuth(self::CLIENT_ID, self::CLIENT_SECRET, 'https://api.test/api/v1', new GuzzleClient(['handler' => $stack]));
    }

    private function json(array $payload, int $status = 200, array $headers = []): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'] + $headers, (string) json_encode($payload));
    }

    private function sentBody(int $i = 0): array
    {
        return json_decode((string) $this->history[$i]['request']->getBody(), true);
    }

    private function tokenBody(): array
    {
        return [
            'access_token' => 'nxi_' . str_repeat('1', 64), 'token_type' => 'Bearer', 'expires_in' => 900, 'refresh_token' => 'nxr_' . str_repeat('2', 64), 'refresh_token_expires_in' => 100,
            'scope' => 'school.students.read school.attendance.read', 'installation_id' => 'i1',
            'claims' => ['iss' => 'nexora-marketplace', 'aud' => self::CLIENT_ID, 'sub' => 'u1', 'userId' => 'u1', 'organizationId' => 'o1', 'environment' => 'LIVE'],
        ];
    }

    public function testExchangeCodeSendsTheDocumentedRequestAndReturnsATypedTokenSet(): void
    {
        $tokens = $this->auth([$this->json($this->tokenBody())])->exchangeCode('nxa_' . str_repeat('c', 43), 'https://app.example.com/nexora/launch');
        $this->assertInstanceOf(TokenSet::class, $tokens);
        $this->assertSame('nxi_' . str_repeat('1', 64), $tokens->accessToken);
        $this->assertSame(['school.students.read', 'school.attendance.read'], $tokens->scope);
        $this->assertSame('i1', $tokens->installationId);
        $this->assertSame('u1', $tokens->claims['userId']);
        $this->assertEqualsWithDelta(time() + 900, $tokens->expiresAt, 5);
        $this->assertFalse($tokens->isExpiring());
        $this->assertTrue($tokens->isExpiring(901));
        $req = $this->history[0]['request'];
        $this->assertSame('POST', $req->getMethod());
        $this->assertSame('/api/v1/marketplace/oauth/token', $req->getUri()->getPath());
        $this->assertSame('', $req->getUri()->getQuery(), 'credentials are never in the URL');
        $this->assertSame(['grant_type' => 'authorization_code', 'code' => 'nxa_' . str_repeat('c', 43), 'redirect_uri' => 'https://app.example.com/nexora/launch', 'client_id' => self::CLIENT_ID, 'client_secret' => self::CLIENT_SECRET], $this->sentBody());
    }

    public function testRefreshAndCodeVerifier(): void
    {
        $auth = $this->auth([$this->json($this->tokenBody()), $this->json($this->tokenBody())]);
        $auth->exchangeCode('nxa_x', 'https://app.example.com/l', str_repeat('v', 50));
        $this->assertSame(str_repeat('v', 50), $this->sentBody(0)['code_verifier']);
        $auth->refresh('nxr_' . str_repeat('3', 64));
        $this->assertSame('refresh_token', $this->sentBody(1)['grant_type']);
        $this->assertArrayNotHasKey('code', $this->sentBody(1));
    }

    public function testATokenSetWithoutClaimsStillWorks(): void
    {
        $body = $this->tokenBody();
        unset($body['claims']);
        $this->assertNull($this->auth([$this->json($body)])->refresh('nxr_x')->claims);
    }

    public function testErrorsKeepCodeAliasStatusAndRequestId(): void
    {
        $auth = $this->auth([$this->json(['error' => ['code' => 'INVALID_GRANT', 'alias' => 'AUTHORIZATION_CODE_EXPIRED', 'message' => 'bad code', 'requestId' => 'req_1']], 400)]);
        try {
            $auth->exchangeCode('nxa_x', 'https://app.example.com/l');
            $this->fail('expected an exception');
        } catch (ValidationException $e) {
            $this->assertSame('INVALID_GRANT', $e->getErrorCode());
            $this->assertSame('AUTHORIZATION_CODE_EXPIRED', $e->getAlias());
            $this->assertTrue($e->is('AUTHORIZATION_CODE_EXPIRED'));
            $this->assertSame(400, $e->getStatusCode());
            $this->assertSame('req_1', $e->getRequestId());
        }
    }

    public function testStatusMapsToTheSdkExceptionTypes(): void
    {
        foreach ([[401, AuthenticationException::class], [403, PermissionException::class], [429, RateLimitException::class], [500, NexoraException::class]] as [$status, $class]) {
            $auth = $this->auth([$this->json(['error' => ['code' => 'X', 'message' => 'm']], $status, $status === 429 ? ['Retry-After' => '7'] : [])]);
            try {
                $auth->refresh('nxr_x');
                $this->fail('expected ' . $class);
            } catch (NexoraException $e) {
                $this->assertInstanceOf($class, $e);
                if ($e instanceof RateLimitException) {
                    $this->assertSame(7, $e->getRetryAfter());
                }
            }
        }
    }

    public function testIntrospectReturnsClaimsWhenActiveAndInactiveOtherwise(): void
    {
        $auth = $this->auth([$this->json(['active' => true, 'organizationId' => 'o1', 'userId' => 'u1']), $this->json(['active' => false]), $this->json(['unexpected' => true])]);
        $live = $auth->introspect('nxi_' . str_repeat('1', 64));
        $this->assertTrue($live['active']);
        $this->assertSame('o1', $live['organizationId']);
        $this->assertSame('/api/v1/marketplace/oauth/introspect', $this->history[0]['request']->getUri()->getPath());
        $this->assertSame(['active' => false], $auth->introspect('nxi_x'));
        $this->assertSame(['active' => false], $auth->introspect('nxi_x'), 'anything that is not an explicit active:true is inactive');
    }

    public function testWebhookSecretPostsTheTokenAndReturnsTheSecret(): void
    {
        $auth = $this->auth([$this->json(['secret' => 'whsec_new', 'version' => 3, 'previousSecret' => 'whsec_old'])]);
        $out = $auth->webhookSecret('nxi_' . str_repeat('1', 64));
        $this->assertSame('whsec_new', $out['secret']);
        $this->assertSame('whsec_old', $out['previousSecret']);
        $this->assertSame('/api/v1/marketplace/oauth/webhook-secret', $this->history[0]['request']->getUri()->getPath());
        $this->expectException(ValidationException::class);
        $auth->webhookSecret('');
    }

    public function testRevokePostsTheToken(): void
    {
        $auth = $this->auth([$this->json([])]);
        $auth->revoke('nxr_' . str_repeat('4', 64));
        $this->assertSame('/api/v1/marketplace/oauth/revoke', $this->history[0]['request']->getUri()->getPath());
        $this->assertSame('nxr_' . str_repeat('4', 64), $this->sentBody()['token']);
    }

    public function testRequiredArgumentsAreCheckedBeforeAnythingIsSent(): void
    {
        $auth = $this->auth([]);
        foreach ([fn () => $auth->exchangeCode('', 'https://x'), fn () => $auth->exchangeCode('nxa_x', ''), fn () => $auth->refresh(''), fn () => $auth->introspect(''), fn () => $auth->revoke('')] as $call) {
            try {
                $call();
                $this->fail('expected a ValidationException');
            } catch (ValidationException $e) {
                $this->assertSame('INVALID_REQUEST', $e->getErrorCode());
            }
        }
        $this->expectException(ValidationException::class);
        new MarketplaceAuth('', '');
    }

    public function testNetworkFailuresBecomeSdkExceptionsAndTheSecretNeverLeaks(): void
    {
        $auth = $this->auth([new ConnectException('down', new Request('POST', 'https://api.test'))]);
        try {
            $auth->refresh('nxr_x');
            $this->fail('expected a NetworkException');
        } catch (NetworkException $e) {
            $this->assertStringNotContainsString(self::CLIENT_SECRET, $e->getMessage());
        }
        $this->assertStringNotContainsString(self::CLIENT_SECRET, print_r($auth, true), 'print_r/var_dump never show the secret');
        $this->assertStringNotContainsString('nxi_', print_r($this->auth([$this->json($this->tokenBody())])->refresh('nxr_x'), true), 'a TokenSet prints without its tokens');
    }
}
