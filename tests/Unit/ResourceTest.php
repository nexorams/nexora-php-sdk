<?php

declare(strict_types=1);

namespace Nexora\Sdk\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Nexora\Sdk\Nexora;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class ResourceTest extends TestCase
{
    /** @var array<int, array{request: RequestInterface, response: Response}> */
    private array $history = [];

    private function createMockClient(array $responses): Nexora
    {
        $this->history = [];
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($this->history));

        $guzzle = new GuzzleClient(['handler' => $handlerStack]);

        return new Nexora(
            apiKey: 'nx_test_mock_secret_key_123',
            baseUrl: 'https://api.nexoragms.com/developer/v1',
            httpClient: $guzzle
        );
    }

    private function getLastRequest(): RequestInterface
    {
        $this->assertNotEmpty($this->history, 'No HTTP requests recorded in mock history.');
        return end($this->history)['request'];
    }

    // ── Organizations Tests ──────────────────────────────────────────────────

    public function testOrganizationsCreateSendsIdempotencyKeyAndBearer(): void
    {
        $mockResponse = new Response(201, ['Content-Type' => 'application/json'], (string) json_encode([
            'success' => true,
            'data' => [
                'id' => 'org_123',
                'name' => 'Apex Specialist Hospital',
                'type' => 'HOSPITAL',
                'environment' => 'TEST',
            ],
        ]));

        $nexora = $this->createMockClient([$mockResponse]);

        $result = $nexora->organizations()->create([
            'name' => 'Apex Specialist Hospital',
            'type' => 'HOSPITAL',
            'country' => 'NG',
        ], 'idempotency-key-001');

        $req = $this->getLastRequest();

        $this->assertSame('POST', $req->getMethod());
        $this->assertSame('/developer/v1/organizations', $req->getUri()->getPath());
        $this->assertSame('Bearer nx_test_mock_secret_key_123', $req->getHeaderLine('Authorization'));
        $this->assertSame('idempotency-key-001', $req->getHeaderLine('Idempotency-Key'));
        $this->assertSame('org_123', $result['id']);
    }

    public function testOrganizationsListPreservesPaginationEnvelope(): void
    {
        $mockResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => [
                ['id' => 'org_1', 'name' => 'Org One'],
                ['id' => 'org_2', 'name' => 'Org Two'],
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 20,
                'total' => 2,
                'totalPages' => 1,
            ],
        ]));

        $nexora = $this->createMockClient([$mockResponse]);

        $result = $nexora->organizations()->list(['page' => 1, 'type' => 'hospital']);

        $req = $this->getLastRequest();
        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/developer/v1/organizations', $req->getUri()->getPath());
        $this->assertStringContainsString('page=1', $req->getUri()->getQuery());
        $this->assertStringContainsString('type=hospital', $req->getUri()->getQuery());

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(2, $result['data']);
        $this->assertSame(2, $result['pagination']['total']);
    }

    public function testOrganizationsGetAndAliases(): void
    {
        $mockResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => ['id' => 'org_999', 'name' => 'St Jude'],
        ]));

        $nexora = $this->createMockClient([$mockResponse]);
        $result = $nexora->organizations()->get('org_999');

        $req = $this->getLastRequest();
        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/developer/v1/organizations/org_999', $req->getUri()->getPath());
        $this->assertSame('St Jude', $result['name']);
    }

    public function testOrganizationsUpdate(): void
    {
        $mockResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => ['id' => 'org_999', 'name' => 'ST JUDE HOSPITAL'],
        ]));

        $nexora = $this->createMockClient([$mockResponse]);
        $result = $nexora->organizations()->update('org_999', ['name' => 'ST JUDE HOSPITAL']);

        $req = $this->getLastRequest();
        $this->assertSame('PATCH', $req->getMethod());
        $this->assertSame('/developer/v1/organizations/org_999', $req->getUri()->getPath());
        $this->assertSame('ST JUDE HOSPITAL', $result['name']);
    }

    public function testOrganizationsUpdateModules(): void
    {
        $mockResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => ['organizationId' => 'org_999', 'activeFeatures' => ['telemedicine', 'pharmacy']],
        ]));

        $nexora = $this->createMockClient([$mockResponse]);
        $result = $nexora->organizations()->updateModules('org_999', ['telemedicine', 'pharmacy']);

        $req = $this->getLastRequest();
        $this->assertSame('PATCH', $req->getMethod());
        $this->assertSame('/developer/v1/organizations/org_999/modules', $req->getUri()->getPath());
        $this->assertSame(['telemedicine', 'pharmacy'], $result['activeFeatures']);
    }

    public function testOrganizationsSubscriptionAndDomains(): void
    {
        $subResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => ['organizationId' => 'org_1', 'planName' => 'Enterprise Healthcare', 'status' => 'TRIAL'],
        ]));
        $domainsResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => [['id' => 'dom_1', 'hostname' => 'portal.stjude.org']],
        ]));

        $nexora = $this->createMockClient([$subResponse, $domainsResponse]);

        $sub = $nexora->organizations()->getSubscription('org_1');
        $this->assertSame('Enterprise Healthcare', $sub['planName']);

        $domains = $nexora->organizations()->getDomains('org_1');
        $this->assertSame('portal.stjude.org', $domains[0]['hostname']);
    }

    // ── Users Tests ──────────────────────────────────────────────────────────

    public function testUsersCreateAndList(): void
    {
        $createResponse = new Response(201, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => ['id' => 'usr_1', 'email' => 'dr.clara@example.com', 'role' => 'doctor'],
        ]));
        $listResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => [['id' => 'usr_1', 'email' => 'dr.clara@example.com', 'role' => 'doctor']],
        ]));

        $nexora = $this->createMockClient([$createResponse, $listResponse]);

        $user = $nexora->users()->create('org_1', [
            'firstName' => 'Clara',
            'lastName' => 'Oswald',
            'email' => 'dr.clara@example.com',
            'role' => 'doctor',
        ]);
        $this->assertSame('usr_1', $user['id']);

        $users = $nexora->users()->list('org_1', ['role' => 'doctor']);
        $this->assertCount(1, $users);
        $this->assertSame('doctor', $users[0]['role']);
    }

    // ── Modules Tests ────────────────────────────────────────────────────────

    public function testModulesListAndCreditsWithSemanticUnlimited(): void
    {
        $listResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => [
                ['key' => 'telemedicine', 'name' => 'Telemedicine Consultations'],
            ],
        ]));
        $creditsResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'success' => true,
            'data' => [
                'limit' => null,
                'usedCredits' => 87,
                'remainingCredits' => null,
                'unlimited' => true,
                'isUnlimited' => true,
                'overLimit' => false,
            ],
        ]));

        $nexora = $this->createMockClient([$listResponse, $creditsResponse]);

        $modules = $nexora->modules()->list(['sector' => 'hospital']);
        $this->assertSame('telemedicine', $modules[0]['key']);

        $credits = $nexora->modules()->credits();
        $this->assertNull($credits['limit']);
        $this->assertTrue($credits['unlimited']);
        $this->assertSame(87, $credits['usedCredits']);
    }

    // ── Plans & Subscriptions Tests ──────────────────────────────────────────

    public function testPlansListAndSubscriptionGet(): void
    {
        $plansResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => [
                ['name' => 'Hospital Pro', 'tier' => 'PRO', 'monthlyPrice' => 150],
            ],
        ]));
        $subResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => ['organizationId' => 'org_123', 'status' => 'ACTIVE'],
        ]));

        $nexora = $this->createMockClient([$plansResponse, $subResponse]);

        $plans = $nexora->plans()->list(['productContext' => 'ORGANIZATION']);
        $this->assertSame('Hospital Pro', $plans[0]['name']);

        $sub = $nexora->subscriptions()->get('org_123');
        $this->assertSame('ACTIVE', $sub['status']);
    }

    // ── Domains Tests ────────────────────────────────────────────────────────

    public function testDomainsCreateAndVerify(): void
    {
        $createResponse = new Response(201, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => ['id' => 'dom_1', 'hostname' => 'clinic.example.com', 'status' => 'PENDING'],
        ]));
        $verifyResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => ['verified' => true, 'status' => 'ACTIVE'],
        ]));

        $nexora = $this->createMockClient([$createResponse, $verifyResponse]);

        $domain = $nexora->domains()->create('org_1', 'clinic.example.com', true);
        $this->assertSame('PENDING', $domain['status']);

        $outcome = $nexora->domains()->verify('org_1', 'dom_1');
        $this->assertTrue($outcome['verified']);
    }

    // ── Webhooks & Deliveries Tests ──────────────────────────────────────────

    public function testWebhooksCrudAndSecretRotation(): void
    {
        $createResponse = new Response(201, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => [
                'id' => 'wh_1',
                'url' => 'https://example.com/webhooks',
                'secret' => 'whsec_secret_token_123',
            ],
        ]));
        $rotateResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => [
                'id' => 'wh_1',
                'secret' => 'whsec_rotated_token_456',
            ],
        ]));

        $nexora = $this->createMockClient([$createResponse, $rotateResponse]);

        $wh = $nexora->webhooks()->create([
            'url' => 'https://example.com/webhooks',
            'events' => ['organization.provisioned'],
        ]);
        $this->assertSame('whsec_secret_token_123', $wh['secret']);

        $rotated = $nexora->webhooks()->rotateSecret('wh_1');
        $this->assertSame('whsec_rotated_token_456', $rotated['secret']);
    }

    public function testDeliveriesListAndRetry(): void
    {
        $listResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => [['id' => 'del_1', 'status' => 'FAILED']],
            'pagination' => ['page' => 1, 'total' => 1],
        ]));
        $retryResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => ['id' => 'del_1', 'status' => 'RETRY_SCHEDULED'],
        ]));

        $nexora = $this->createMockClient([$listResponse, $retryResponse]);

        $list = $nexora->deliveries()->list(['status' => 'FAILED']);
        $this->assertSame('FAILED', $list['data'][0]['status']);

        $retry = $nexora->deliveries()->retry('del_1');
        $this->assertSame('RETRY_SCHEDULED', $retry['status']);
    }

    // ── Usage & Project Tests ────────────────────────────────────────────────

    public function testUsageSummaryAndGetProject(): void
    {
        $usageResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => ['totalRequests' => 450, 'successCount' => 445],
        ]));
        $projectResponse = new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'data' => ['id' => 'proj_1', 'name' => 'Main Workspace', 'environment' => 'TEST'],
        ]));

        $nexora = $this->createMockClient([$usageResponse, $projectResponse]);

        $usage = $nexora->usage()->summary(['period' => '2026-09']);
        $this->assertSame(450, $usage['totalRequests']);

        $project = $nexora->usage()->getProject();
        $this->assertSame('Main Workspace', $project['name']);
    }
}
